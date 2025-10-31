<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\CatalogBundle\DataFixtures\CatalogFixtures;
use Tourze\CatalogBundle\DataFixtures\CatalogTypeFixtures;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\CatalogBundle\Entity\CatalogType;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;

class CategoryAttributeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Attribute $colorAttribute */
        $colorAttribute = $this->getReference(AttributeFixtures::ATTR_COLOR, Attribute::class);
        /** @var Attribute $sizeAttribute */
        $sizeAttribute = $this->getReference(AttributeFixtures::ATTR_SIZE, Attribute::class);
        /** @var Attribute $materialAttribute */
        $materialAttribute = $this->getReference(AttributeFixtures::ATTR_MATERIAL, Attribute::class);
        /** @var AttributeGroup $basicGroup */
        $basicGroup = $this->getReference(AttributeGroupFixtures::GROUP_BASIC, AttributeGroup::class);
        /** @var AttributeGroup $appearanceGroup */
        $appearanceGroup = $this->getReference(AttributeGroupFixtures::GROUP_APPEARANCE, AttributeGroup::class);

        // 创建测试用的 Catalog 实体（写入模式）
        /** @var CatalogType $productType */
        $productType = $this->getReference(CatalogTypeFixtures::REFERENCE_PRODUCT_TYPE, CatalogType::class);

        $catalog = new Catalog();
        $catalog->setType($productType);
        $catalog->setName('测试类目');
        $catalog->setDescription('产品属性测试用类目');
        $catalog->setSortOrder(99);
        $catalog->setEnabled(true);
        $manager->persist($catalog);

        $categoryAttributeConfigs = [
            [
                'attribute' => $colorAttribute,
                'group' => $appearanceGroup,
                'isRequired' => true,
                'isVisible' => true,
                'defaultValue' => null,
                'allowedValues' => null,
                'sortOrder' => 1,
                'config' => ['display_type' => 'color_picker'],
                'isInherited' => false,
            ],
            [
                'attribute' => $sizeAttribute,
                'group' => $basicGroup,
                'isRequired' => true,
                'isVisible' => true,
                'defaultValue' => null,
                'allowedValues' => null,
                'sortOrder' => 2,
                'config' => ['display_type' => 'size_chart'],
                'isInherited' => false,
            ],
            [
                'attribute' => $materialAttribute,
                'group' => $basicGroup,
                'isRequired' => false,
                'isVisible' => true,
                'defaultValue' => 'cotton',
                'allowedValues' => ['cotton', 'polyester'],
                'sortOrder' => 3,
                'config' => null,
                'isInherited' => false,
            ],
        ];

        foreach ($categoryAttributeConfigs as $index => $config) {
            $categoryAttribute = new CategoryAttribute();
            $categoryAttribute->setCategory($catalog);
            $categoryAttribute->setAttribute($config['attribute']);
            $categoryAttribute->setGroup($config['group']);
            $categoryAttribute->setIsRequired($config['isRequired']);
            $categoryAttribute->setIsVisible($config['isVisible']);
            $categoryAttribute->setDefaultValue($config['defaultValue']);
            $categoryAttribute->setAllowedValues($config['allowedValues']);
            $categoryAttribute->setSortOrder($config['sortOrder']);
            $categoryAttribute->setConfig($config['config']);
            $categoryAttribute->setIsInherited($config['isInherited']);

            $manager->persist($categoryAttribute);
            $this->addReference("category-attribute-{$index}", $categoryAttribute);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AttributeFixtures::class,
            AttributeGroupFixtures::class,
            CatalogTypeFixtures::class,
        ];
    }
}
