<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\SkuAttribute;
use Tourze\ProductAttributeBundle\Exception\AttributeException;
use Tourze\ProductCoreBundle\Entity\Sku;

/**
 * @extends ServiceEntityRepository<SkuAttribute>
 */
#[AsRepository(entityClass: SkuAttribute::class)]
class SkuAttributeRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly AttributeRepository $attributeRepository,
    ) {
        parent::__construct($registry, SkuAttribute::class);
    }

    /**
     * 查找SKU的所有属性
     *
     * @return SkuAttribute[]
     */
    public function findBySku(Sku $sku): array
    {
        /** @var array<SkuAttribute> */
        return $this->createQueryBuilder('ska')
            ->andWhere('ska.sku = :sku')
            ->setParameter('sku', $sku)
            ->orderBy('ska.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据SKU和属性名查找
     */
    public function findBySkuAndName(Sku $sku, string $name): ?SkuAttribute
    {
        /** @var SkuAttribute|null */
        return $this->createQueryBuilder('ska')
            ->andWhere('ska.sku = :sku')
            ->andWhere('ska.name = :name')
            ->setParameter('sku', $sku)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找SKU的特定属性
     */
    public function findBySkuAndAttribute(Sku $sku, Attribute $attribute): ?SkuAttribute
    {
        /** @var SkuAttribute|null */
        return $this->createQueryBuilder('ska')
            ->andWhere('ska.sku = :sku')
            ->andWhere('ska.attribute = :attribute')
            ->setParameter('sku', $sku)
            ->setParameter('attribute', $attribute)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 批量保存SKU属性
     *
     * @param array<string, mixed>[] $attributesData
     * @return SkuAttribute[]
     */
    public function batchSave(Sku $sku, array $attributesData): array
    {
        $skuAttributes = [];

        foreach ($attributesData as $data) {
            // 支持通过名称或对象传入属性
            /** @var Attribute|null $attribute */
            $attribute = $data['attribute'] ?? null;
            if (null === $attribute && isset($data['name'])) {
                $attribute = $this->attributeRepository
                    ->findOneBy(['name' => $data['name']])
                ;
            }

            if (!$attribute instanceof Attribute) {
                continue; // 跳过无效的属性
            }

            /** @var string $value */
            $value = $data['value'];
            assert(is_string($value));

            // 查找或创建
            $skuAttribute = $this->findBySkuAndAttribute($sku, $attribute);
            if (null === $skuAttribute) {
                $skuAttribute = new SkuAttribute();
                $skuAttribute->setSku($sku);
                $skuAttribute->setAttribute($attribute);
            }

            $skuAttribute->setValue($value);

            // 验证属性值是否有效
            if (!$skuAttribute->isValid()) {
                throw AttributeException::invalidAttributeValue($attribute->getName());
            }

            $this->getEntityManager()->persist($skuAttribute);
            $skuAttributes[] = $skuAttribute;
        }

        $this->getEntityManager()->flush();

        return $skuAttributes;
    }

    /**
     * 删除SKU的所有属性
     */
    public function removeAllBySku(Sku $sku): void
    {
        $this->createQueryBuilder('ska')
            ->delete()
            ->andWhere('ska.sku = :sku')
            ->setParameter('sku', $sku)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 根据属性值组合查找SKU
     *
     * @param array<string, mixed>[] $attributeValuePairs
     */
    public function findSkuByAttributeValues(array $attributeValuePairs): ?Sku
    {
        if (0 === count($attributeValuePairs)) {
            return null;
        }

        // 简单直接的方案：查找第一个条件匹配的SkuAttribute，然后验证其他条件
        $firstPair = $attributeValuePairs[0];
        /** @var Attribute $firstAttribute */
        $firstAttribute = $firstPair['attribute'];
        assert($firstAttribute instanceof Attribute);
        $skuAttributes = $this->findBy([
            'attribute' => $firstAttribute,
            'value' => $firstPair['value'],
        ]);

        foreach ($skuAttributes as $skuAttribute) {
            $sku = $skuAttribute->getSku();
            if (null === $sku) {
                continue;
            }

            // 验证这个SKU是否满足所有条件
            $matches = 0;
            foreach ($attributeValuePairs as $pair) {
                /** @var Attribute $pairAttribute */
                $pairAttribute = $pair['attribute'];
                assert($pairAttribute instanceof Attribute);
                $existing = $this->findBySkuAndAttribute($sku, $pairAttribute);
                if (null !== $existing && $existing->getValue() === $pair['value']) {
                    ++$matches;
                }
            }

            if ($matches === count($attributeValuePairs)) {
                return $sku;
            }
        }

        return null;
    }

    /**
     * 检查属性值组合是否唯一
     *
     * @param array<string, mixed>[] $attributeValuePairs
     */
    public function isAttributeCombinationUnique(array $attributeValuePairs, ?string $excludeSkuId = null): bool
    {
        $sku = $this->findSkuByAttributeValues($attributeValuePairs);

        if (null === $sku) {
            return true;
        }

        if (null !== $excludeSkuId && $sku->getId() === $excludeSkuId) {
            return true;
        }

        return false;
    }

    /**
     * 保存SKU属性实体
     */
    public function save(SkuAttribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除SKU属性实体
     */
    public function remove(SkuAttribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取SPU下所有SKU的属性值组合
     *
     * @return array<array<string, mixed>>
     */
    public function getAttributeCombinationsBySpu(string $spuId): array
    {
        /** @var array<array<string, mixed>> */
        return $this->createQueryBuilder('ska')
            ->select('s.id as sku_id', 'a.id as attribute_id', 'a.name as attribute_name', 'v.id as value_id', 'v.value as value_text')
            ->join('ska.sku', 's')
            ->join('ska.attribute', 'a')
            ->join('ska.value', 'v')
            ->andWhere('s.spu = :spuId')
            ->setParameter('spuId', $spuId)
            ->orderBy('s.id', 'ASC')
            ->addOrderBy('a.sortOrder', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
