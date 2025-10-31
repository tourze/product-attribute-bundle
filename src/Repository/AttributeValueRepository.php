<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

/**
 * @extends ServiceEntityRepository<AttributeValue>
 */
#[AsRepository(entityClass: AttributeValue::class)]
class AttributeValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttributeValue::class);
    }

    /**
     * 查找属性的所有活跃值
     *
     * @return AttributeValue[]
     */
    public function findActiveValuesByAttribute(Attribute $attribute): array
    {
        /** @var array<AttributeValue> */
        return $this->createQueryBuilder('v')
            ->andWhere('v.attribute = :attribute')
            ->andWhere('v.status = :status')
            ->setParameter('attribute', $attribute)
            ->setParameter('status', AttributeStatus::ACTIVE)
            ->orderBy('v.sortOrder', 'ASC')
            ->addOrderBy('v.value', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据编码查找属性值
     */
    public function findByAttributeAndCode(Attribute $attribute, string $code): ?AttributeValue
    {
        /** @var AttributeValue|null */
        return $this->createQueryBuilder('v')
            ->andWhere('v.attribute = :attribute')
            ->andWhere('v.code = :code')
            ->setParameter('attribute', $attribute)
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据值内容查找属性值（包括别名）
     */
    public function findByAttributeAndValue(Attribute $attribute, string $value): ?AttributeValue
    {
        // 首先尝试直接值匹配
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.attribute = :attribute')
            ->andWhere('v.value = :value')
            ->setParameter('attribute', $attribute)
            ->setParameter('value', $value)
        ;

        /** @var AttributeValue|null */
        $result = $qb->getQuery()->getOneOrNullResult();
        if (null !== $result) {
            return $result;
        }

        // 如果直接值匹配失败，则查询所有该属性的值，在PHP中检查aliases
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.attribute = :attribute')
            ->andWhere('v.aliases IS NOT NULL')
            ->setParameter('attribute', $attribute)
        ;

        /** @var array<AttributeValue> */
        $results = $qb->getQuery()->getResult();

        foreach ($results as $attributeValue) {
            $aliases = $attributeValue->getAliases();
            if (is_array($aliases) && in_array($value, $aliases, true)) {
                return $attributeValue;
            }
        }

        return null;
    }

    /**
     * 根据ID列表查找属性值
     *
     * @param int[] $ids
     * @return AttributeValue[]
     */
    public function findByIds(array $ids): array
    {
        if (0 === count($ids)) {
            return [];
        }

        /** @var array<AttributeValue> */
        return $this->createQueryBuilder('v')
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 检查编码是否已存在
     */
    public function isCodeExists(Attribute $attribute, string $code, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.attribute = :attribute')
            ->andWhere('v.code = :code')
            ->setParameter('attribute', $attribute)
            ->setParameter('code', $code)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('v.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 批量创建属性值
     *
     * @param array<string, mixed>[] $valuesData
     * @return AttributeValue[]
     */
    public function batchCreate(Attribute $attribute, array $valuesData): array
    {
        $values = [];
        foreach ($valuesData as $data) {
            $value = new AttributeValue();
            $value->setAttribute($attribute);

            $code = $data['code'] ?? '';
            assert(is_string($code));
            $value->setCode($code);

            $valueText = $data['value'] ?? '';
            assert(is_string($valueText));
            $value->setValue($valueText);

            $sortOrder = $data['sortOrder'] ?? 0;
            assert(is_int($sortOrder));
            $value->setSortOrder($sortOrder);

            if (isset($data['aliases'])) {
                /** @var array<string>|null $aliases */
                $aliases = $data['aliases'];
                assert(is_array($aliases) || null === $aliases);
                $value->setAliases($aliases);
            }

            if (isset($data['colorValue'])) {
                $colorValue = $data['colorValue'];
                assert(is_string($colorValue));
                $value->setColorValue($colorValue);
            }

            if (isset($data['imageUrl'])) {
                $imageUrl = $data['imageUrl'];
                assert(is_string($imageUrl));
                $value->setImageUrl($imageUrl);
            }

            $this->getEntityManager()->persist($value);
            $values[] = $value;
        }

        $this->getEntityManager()->flush();

        return $values;
    }

    /**
     * 保存属性值实体
     */
    public function save(AttributeValue $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除属性值实体
     */
    public function remove(AttributeValue $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
