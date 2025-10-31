<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

/**
 * @extends ServiceEntityRepository<CategoryAttribute>
 */
#[AsRepository(entityClass: CategoryAttribute::class)]
class CategoryAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryAttribute::class);
    }

    /**
     * 查找类目的所有属性（包括继承的）
     *
     * @return CategoryAttribute[]
     */
    public function findByCategoryWithInheritance(Catalog $category): array
    {
        $attributes = $this->findByCategory($category);
        $attributes = $this->mergeParentAttributes($attributes, $category);

        return $this->sortAttributes($attributes);
    }

    /**
     * 合并父类目的属性
     *
     * @param CategoryAttribute[] $attributes
     * @return CategoryAttribute[]
     */
    private function mergeParentAttributes(array $attributes, Catalog $category): array
    {
        $parent = $category->getParent();
        while (null !== $parent) {
            $parentAttributes = $this->findByCategory($parent);
            $attributes = $this->addNonDuplicateAttributes($attributes, $parentAttributes);
            $parent = $parent->getParent();
        }

        return $attributes;
    }

    /**
     * 添加不重复的属性
     *
     * @param CategoryAttribute[] $attributes
     * @param CategoryAttribute[] $parentAttributes
     * @return CategoryAttribute[]
     */
    private function addNonDuplicateAttributes(array $attributes, array $parentAttributes): array
    {
        $existingIds = $this->extractAttributeIds($attributes);

        foreach ($parentAttributes as $parentAttr) {
            $attribute = $parentAttr->getAttribute();
            if (null !== $attribute && !in_array($attribute->getId(), $existingIds, true)) {
                $parentAttr->setIsInherited(true);
                $attributes[] = $parentAttr;
            }
        }

        return $attributes;
    }

    /**
     * 提取属性ID数组
     *
     * @param CategoryAttribute[] $attributes
     * @return string[]
     */
    private function extractAttributeIds(array $attributes): array
    {
        return array_filter(array_map(
            fn (CategoryAttribute $attr) => $attr->getAttribute()?->getId(),
            $attributes
        ), fn ($id) => null !== $id);
    }

    /**
     * 按排序权重排序属性
     *
     * @param CategoryAttribute[] $attributes
     * @return CategoryAttribute[]
     */
    private function sortAttributes(array $attributes): array
    {
        usort($attributes, function (CategoryAttribute $a, CategoryAttribute $b) {
            if ($a->getSortOrder() === $b->getSortOrder()) {
                $aAttr = $a->getAttribute();
                $bAttr = $b->getAttribute();
                $aName = null !== $aAttr ? $aAttr->getName() : '';
                $bName = null !== $bAttr ? $bAttr->getName() : '';

                return strcmp($aName, $bName);
            }

            return $a->getSortOrder() - $b->getSortOrder();
        });

        return $attributes;
    }

    /**
     * 查找类目的直接属性（不包括继承的）
     *
     * @return CategoryAttribute[]
     */
    public function findByCategory(Catalog $category): array
    {
        /** @var array<CategoryAttribute> */
        return $this->createQueryBuilder('ca')
            ->join('ca.attribute', 'a')
            ->andWhere('ca.category = :category')
            ->andWhere('ca.isVisible = :visible')
            ->andWhere('a.status = :status')
            ->setParameter('category', $category)
            ->setParameter('visible', true)
            ->setParameter('status', AttributeStatus::ACTIVE)
            ->orderBy('ca.sortOrder', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找类目的销售属性
     *
     * @return CategoryAttribute[]
     */
    public function findSalesAttributesByCategory(Catalog $category): array
    {
        /** @var array<CategoryAttribute> */
        return $this->createQueryBuilder('ca')
            ->join('ca.attribute', 'a')
            ->andWhere('ca.category = :category')
            ->andWhere('ca.isVisible = :visible')
            ->andWhere('a.status = :status')
            ->andWhere('a.type = :type')
            ->setParameter('category', $category)
            ->setParameter('visible', true)
            ->setParameter('status', AttributeStatus::ACTIVE)
            ->setParameter('type', 'sales')
            ->orderBy('ca.sortOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找类目的必填属性
     *
     * @return CategoryAttribute[]
     */
    public function findRequiredAttributesByCategory(Catalog $category): array
    {
        $qb = $this->createQueryBuilder('ca')
            ->join('ca.attribute', 'a')
            ->andWhere('ca.category = :category')
            ->andWhere('ca.isVisible = :visible')
            ->andWhere('a.status = :status')
            ->andWhere('(ca.isRequired = true OR (ca.isRequired IS NULL AND a.isRequired = true))')
            ->setParameter('category', $category)
            ->setParameter('visible', true)
            ->setParameter('status', AttributeStatus::ACTIVE)
            ->orderBy('ca.sortOrder', 'ASC')
        ;

        /** @var array<CategoryAttribute> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 批量关联属性到类目
     *
     * @param array<string, mixed>[] $attributesData
     * @return CategoryAttribute[]
     */
    public function batchAssociate(Catalog $category, array $attributesData): array
    {
        $associations = [];

        foreach ($attributesData as $data) {
            $ca = new CategoryAttribute();
            $ca->setCategory($category);

            $attribute = $data['attribute'];
            assert($attribute instanceof Attribute || null === $attribute);
            $ca->setAttribute($attribute);

            $this->applyOptionalFields($ca, $data);

            $this->getEntityManager()->persist($ca);
            $associations[] = $ca;
        }

        $this->getEntityManager()->flush();

        return $associations;
    }

    /**
     * 应用可选字段到 CategoryAttribute
     * @param array<string, mixed> $data
     */
    private function applyOptionalFields(CategoryAttribute $ca, array $data): void
    {
        if (isset($data['group'])) {
            /** @var AttributeGroup|null $group */
            $group = $data['group'];
            assert($group instanceof AttributeGroup || null === $group);
            $ca->setGroup($group);
        }

        if (isset($data['isRequired'])) {
            $isRequired = $data['isRequired'];
            assert(is_bool($isRequired));
            $ca->setIsRequired($isRequired);
        }

        $ca->setIsVisible(isset($data['isVisible']) && is_bool($data['isVisible']) ? $data['isVisible'] : true);

        if (isset($data['defaultValue'])) {
            $defaultValue = $data['defaultValue'];
            assert(is_string($defaultValue));
            $ca->setDefaultValue($defaultValue);
        }

        if (isset($data['allowedValues'])) {
            /** @var array<bool|float|int|string> $allowedValues */
            $allowedValues = $data['allowedValues'];
            assert(is_array($allowedValues));
            $ca->setAllowedValues($allowedValues);
        }

        $ca->setSortOrder(isset($data['sortOrder']) && is_int($data['sortOrder']) ? $data['sortOrder'] : 0);

        if (isset($data['config'])) {
            /** @var array<string, mixed> $config */
            $config = $data['config'];
            assert(is_array($config));
            $ca->setConfig($config);
        }
    }

    /**
     * 保存类目属性实体
     */
    public function save(CategoryAttribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除类目的所有属性关联
     */
    public function removeAllByCategory(Catalog $category): void
    {
        $this->createQueryBuilder('ca')
            ->delete()
            ->andWhere('ca.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 删除类目属性实体
     */
    public function remove(CategoryAttribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
