<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\SpuAttribute;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @extends ServiceEntityRepository<SpuAttribute>
 */
#[AsRepository(entityClass: SpuAttribute::class)]
class SpuAttributeRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly AttributeRepository $attributeRepository,
    ) {
        parent::__construct($registry, SpuAttribute::class);
    }

    /**
     * 查找SPU的所有属性
     *
     * @return SpuAttribute[]
     */
    public function findBySpu(Spu $spu): array
    {
        /** @var array<SpuAttribute> */
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.spu = :spu')
            ->setParameter('spu', $spu)
            ->orderBy('sa.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据SPU和属性名查找
     */
    public function findBySpuAndName(Spu $spu, string $name): ?SpuAttribute
    {
        /** @var SpuAttribute|null */
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.spu = :spu')
            ->andWhere('sa.name = :name')
            ->setParameter('spu', $spu)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找SPU的特定属性
     */
    public function findBySpuAndAttribute(Spu $spu, Attribute $attribute): ?SpuAttribute
    {
        /** @var SpuAttribute|null */
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.spu = :spu')
            ->andWhere('sa.attribute = :attribute')
            ->setParameter('spu', $spu)
            ->setParameter('attribute', $attribute)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 批量保存SPU属性
     *
     * @param array<string, mixed>[] $attributesData
     * @return SpuAttribute[]
     */
    public function batchSave(Spu $spu, array $attributesData): array
    {
        $spuAttributes = [];

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

            // 查找或创建
            $spuAttribute = $this->findBySpuAndAttribute($spu, $attribute);
            if (null === $spuAttribute) {
                $spuAttribute = new SpuAttribute();
                $spuAttribute->setSpu($spu);
                $spuAttribute->setAttribute($attribute);
                $spuAttribute->setName($attribute->getName());
            }

            $this->applyAttributeValue($spuAttribute, $data);

            $this->getEntityManager()->persist($spuAttribute);
            $spuAttributes[] = $spuAttribute;
        }

        $this->getEntityManager()->flush();

        return $spuAttributes;
    }

    /**
     * 应用属性值到 SpuAttribute
     * @param array<string, mixed> $data
     */
    private function applyAttributeValue(SpuAttribute $spuAttribute, array $data): void
    {
        if (isset($data['valueIds'])) {
            /** @var array<int, string>|null $valueIds */
            $valueIds = $data['valueIds'];
            if (is_array($valueIds) || null === $valueIds) {
                $spuAttribute->setValueIds($valueIds);
            }

            return;
        }

        if (isset($data['valueText'])) {
            /** @var string|null $valueText */
            $valueText = $data['valueText'];
            if (is_string($valueText) || null === $valueText) {
                $spuAttribute->setValueText($valueText);
            }

            return;
        }

        if (isset($data['value'])) {
            /** @var string $value */
            $value = $data['value'];
            if (is_string($value)) {
                $spuAttribute->setValue($value);
            }
        }
    }

    /**
     * 删除SPU的所有属性
     */
    public function removeAllBySpu(Spu $spu): void
    {
        $this->createQueryBuilder('sa')
            ->delete()
            ->andWhere('sa.spu = :spu')
            ->setParameter('spu', $spu)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 删除SPU的特定属性
     */
    public function removeBySpuAndAttribute(Spu $spu, Attribute $attribute): void
    {
        $this->createQueryBuilder('sa')
            ->delete()
            ->andWhere('sa.spu = :spu')
            ->andWhere('sa.attribute = :attribute')
            ->setParameter('spu', $spu)
            ->setParameter('attribute', $attribute)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 保存SPU属性实体
     */
    public function save(SpuAttribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除SPU属性实体
     */
    public function remove(SpuAttribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据属性值查找SPU
     *
     * @param array<string, mixed>[] $attributeValuePairs
     * @return Spu[]
     */
    public function findSpusByAttributeValues(array $attributeValuePairs): array
    {
        if ([] === $attributeValuePairs) {
            return [];
        }

        $candidateSpus = $this->findCandidateSpus($attributeValuePairs[0]);

        return $this->filterSpusByAllConditions($candidateSpus, $attributeValuePairs);
    }

    /**
     * @param array<string, mixed> $firstPair
     * @return Spu[]
     */
    private function findCandidateSpus(array $firstPair): array
    {
        if (isset($firstPair['valueIds'])) {
            $attribute = $firstPair['attribute'];
            assert($attribute instanceof Attribute);
            /** @var array<int> $valueIds */
            $valueIds = $firstPair['valueIds'];
            assert(is_array($valueIds));

            return $this->findSpusByValueIds($attribute, $valueIds);
        }

        if (isset($firstPair['valueText'])) {
            $attribute = $firstPair['attribute'];
            assert($attribute instanceof Attribute);
            $valueText = $firstPair['valueText'];
            assert(is_string($valueText));

            return $this->findSpusByValueText($attribute, $valueText);
        }

        return [];
    }

    /**
     * @param Attribute $attribute
     * @param int[] $valueIds
     * @return Spu[]
     */
    private function findSpusByValueIds(Attribute $attribute, array $valueIds): array
    {
        /** @var array<SpuAttribute> */
        $spuAttributes = $this->createQueryBuilder('sa')
            ->andWhere('sa.attribute = :attr')
            ->andWhere('JSON_CONTAINS(sa.valueIds, :valueIds) = 1')
            ->setParameter('attr', $attribute)
            ->setParameter('valueIds', json_encode($valueIds))
            ->getQuery()
            ->getResult()
        ;

        return $this->extractSpusFromAttributes($spuAttributes);
    }

    /**
     * @param Attribute $attribute
     * @return Spu[]
     */
    private function findSpusByValueText(Attribute $attribute, string $valueText): array
    {
        /** @var array<SpuAttribute> */
        $spuAttributes = $this->createQueryBuilder('sa')
            ->andWhere('sa.attribute = :attr')
            ->andWhere('sa.valueText LIKE :valueText')
            ->setParameter('attr', $attribute)
            ->setParameter('valueText', '%' . $valueText . '%')
            ->getQuery()
            ->getResult()
        ;

        return $this->extractSpusFromAttributes($spuAttributes);
    }

    /**
     * @param SpuAttribute[] $spuAttributes
     * @return Spu[]
     */
    private function extractSpusFromAttributes(array $spuAttributes): array
    {
        $spus = [];
        foreach ($spuAttributes as $spuAttribute) {
            $spu = $spuAttribute->getSpu();
            if (null !== $spu) {
                $spus[] = $spu;
            }
        }

        return $spus;
    }

    /**
     * @param Spu[] $candidateSpus
     * @param array<string, mixed>[] $attributeValuePairs
     * @return Spu[]
     */
    private function filterSpusByAllConditions(array $candidateSpus, array $attributeValuePairs): array
    {
        $matchingSpus = [];
        foreach ($candidateSpus as $spu) {
            if ($this->spuMatchesAllConditions($spu, $attributeValuePairs)) {
                $matchingSpus[] = $spu;
            }
        }

        return $matchingSpus;
    }

    /**
     * @param Spu $spu
     * @param array<string, mixed>[] $attributeValuePairs
     */
    private function spuMatchesAllConditions(object $spu, array $attributeValuePairs): bool
    {
        foreach ($attributeValuePairs as $pair) {
            if (!$this->spuMatchesCondition($spu, $pair)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Spu $spu
     * @param array<string, mixed> $pair
     */
    private function spuMatchesCondition(object $spu, array $pair): bool
    {
        $attribute = $pair['attribute'];
        assert($attribute instanceof Attribute);
        $existing = $this->findBySpuAndAttribute($spu, $attribute);
        if (null === $existing) {
            return false;
        }

        if (isset($pair['valueIds'])) {
            /** @var array<int> $valueIds */
            $valueIds = $pair['valueIds'];
            assert(is_array($valueIds));

            return $this->matchesValueIds($existing, $valueIds);
        }

        if (isset($pair['valueText'])) {
            $valueText = $pair['valueText'];
            assert(is_string($valueText));

            return $this->matchesValueText($existing, $valueText);
        }

        return false;
    }

    /**
     * @param SpuAttribute $existing
     * @param int[] $valueIds
     */
    private function matchesValueIds(SpuAttribute $existing, array $valueIds): bool
    {
        $existingIds = $existing->getValueIds();
        if (null === $existingIds) {
            return false;
        }

        return $existingIds === $valueIds;
    }

    /**
     * @param SpuAttribute $existing
     */
    private function matchesValueText(SpuAttribute $existing, string $valueText): bool
    {
        $existingText = $existing->getValueText() ?? '';

        return str_contains($existingText, $valueText);
    }
}
