<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;
use Tourze\ProductAttributeBundle\Repository\CategoryAttributeRepository;

class CategoryAttributeManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryAttributeRepository $categoryAttributeRepository,
    ) {
    }

    /**
     * 关联属性到类目
     */
    /**
     * @param array<string, mixed> $options
     */
    public function associateAttribute(
        Catalog $category,
        Attribute $attribute,
        ?AttributeGroup $group = null,
        array $options = [],
    ): CategoryAttribute {
        // 检查是否已存在
        $existing = $this->categoryAttributeRepository->findOneBy([
            'category' => $category,
            'attribute' => $attribute,
        ]);

        if (null !== $existing) {
            // 更新现有关联
            return $this->updateCategoryAttribute($existing, array_merge(['group' => $group], $options));
        }

        // 创建新关联
        $categoryAttribute = new CategoryAttribute();
        $categoryAttribute->setCategory($category);
        $categoryAttribute->setAttribute($attribute);
        $categoryAttribute->setGroup($group);

        $this->updateCategoryAttributeFromArray($categoryAttribute, $options);

        $this->entityManager->persist($categoryAttribute);
        $this->entityManager->flush();

        return $categoryAttribute;
    }

    /**
     * 批量关联属性到类目
     */
    /**
     * @param array<array<string, mixed>> $attributesData
     * @return array<CategoryAttribute>
     */
    public function batchAssociate(Catalog $category, array $attributesData): array
    {
        $associations = [];

        foreach ($attributesData as $data) {
            assert(isset($data['attribute']));
            $attribute = $data['attribute'];
            assert($attribute instanceof Attribute);

            $group = $data['group'] ?? null;
            assert($group instanceof AttributeGroup || null === $group);

            $options = $data;
            unset($options['attribute'], $options['group']);

            $associations[] = $this->associateAttribute($category, $attribute, $group, $options);
        }

        return $associations;
    }

    /**
     * 更新类目属性关联
     */
    /**
     * @param array<string, mixed> $data
     */
    public function updateCategoryAttribute(CategoryAttribute $categoryAttribute, array $data): CategoryAttribute
    {
        $this->updateCategoryAttributeFromArray($categoryAttribute, $data);
        $this->entityManager->flush();

        return $categoryAttribute;
    }

    /**
     * 移除类目的属性关联
     */
    public function dissociateAttribute(Catalog $category, Attribute $attribute): void
    {
        $categoryAttribute = $this->categoryAttributeRepository->findOneBy([
            'category' => $category,
            'attribute' => $attribute,
        ]);

        if (null !== $categoryAttribute) {
            $this->entityManager->remove($categoryAttribute);
            $this->entityManager->flush();
        }
    }

    /**
     * 移除类目的所有属性关联
     */
    public function dissociateAllAttributes(Catalog $category): void
    {
        $this->categoryAttributeRepository->removeAllByCategory($category);
    }

    /**
     * 复制属性设置到另一个类目
     */
    /**
     * @return array<CategoryAttribute>
     */
    public function copyAttributesToCategory(Catalog $sourceCategory, Catalog $targetCategory): array
    {
        $sourceAttributes = $this->categoryAttributeRepository->findByCategory($sourceCategory);
        $copied = [];

        foreach ($sourceAttributes as $sourceAttribute) {
            $categoryAttribute = new CategoryAttribute();
            $categoryAttribute->setCategory($targetCategory);
            $categoryAttribute->setAttribute($sourceAttribute->getAttribute());
            $categoryAttribute->setGroup($sourceAttribute->getGroup());
            $categoryAttribute->setIsRequired($sourceAttribute->getIsRequired());
            $categoryAttribute->setIsVisible($sourceAttribute->isVisible());
            $categoryAttribute->setDefaultValue($sourceAttribute->getDefaultValue());
            $categoryAttribute->setAllowedValues($sourceAttribute->getAllowedValues());
            $categoryAttribute->setSortOrder($sourceAttribute->getSortOrder());
            $categoryAttribute->setConfig($sourceAttribute->getConfig());

            $this->entityManager->persist($categoryAttribute);
            $copied[] = $categoryAttribute;
        }

        $this->entityManager->flush();

        return $copied;
    }

    /**
     * 获取类目的所有属性（包括继承的）
     */
    /**
     * @return array<CategoryAttribute>
     */
    public function getCategoryAttributesWithInheritance(Catalog $category): array
    {
        return $this->categoryAttributeRepository->findByCategoryWithInheritance($category);
    }

    /**
     * 获取类目的销售属性
     */
    /**
     * @return array<CategoryAttribute>
     */
    public function getCategorySalesAttributes(Catalog $category): array
    {
        return $this->categoryAttributeRepository->findSalesAttributesByCategory($category);
    }

    /**
     * 获取类目的必填属性
     */
    /**
     * @return array<CategoryAttribute>
     */
    public function getCategoryRequiredAttributes(Catalog $category): array
    {
        return $this->categoryAttributeRepository->findRequiredAttributesByCategory($category);
    }

    /**
     * 从数组更新类目属性关联
     */
    /**
     * @param array<string, mixed> $data
     */
    private function updateCategoryAttributeFromArray(CategoryAttribute $categoryAttribute, array $data): void
    {
        if (isset($data['group'])) {
            /** @var AttributeGroup|null $group */
            $group = $data['group'];
            $categoryAttribute->setGroup($group);
        }

        if (isset($data['isRequired'])) {
            $isRequired = $data['isRequired'];
            assert(is_bool($isRequired));
            $categoryAttribute->setIsRequired($isRequired);
        }

        if (isset($data['isVisible'])) {
            $isVisible = $data['isVisible'];
            assert(is_bool($isVisible));
            $categoryAttribute->setIsVisible($isVisible);
        }

        if (isset($data['defaultValue'])) {
            $defaultValue = $data['defaultValue'];
            assert(is_string($defaultValue));
            $categoryAttribute->setDefaultValue($defaultValue);
        }

        if (isset($data['allowedValues'])) {
            /** @var array<bool|float|int|string> $allowedValues */
            $allowedValues = $data['allowedValues'];
            assert(is_array($allowedValues));
            $categoryAttribute->setAllowedValues($allowedValues);
        }

        if (isset($data['sortOrder'])) {
            $sortOrder = $data['sortOrder'];
            assert(is_int($sortOrder));
            $categoryAttribute->setSortOrder($sortOrder);
        }

        if (isset($data['config'])) {
            /** @var array<string, mixed> $config */
            $config = $data['config'];
            assert(is_array($config));
            $categoryAttribute->setConfig($config);
        }
    }
}
