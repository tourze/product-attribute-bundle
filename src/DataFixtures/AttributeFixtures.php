<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

class AttributeFixtures extends Fixture implements DependentFixtureInterface
{
    public const ATTR_COLOR = 'attribute-color';
    public const ATTR_SIZE = 'attribute-size';
    public const ATTR_MATERIAL = 'attribute-material';
    public const ATTR_BRAND = 'attribute-brand';
    public const ATTR_WEIGHT = 'attribute-weight';
    public const ATTR_POWER = 'attribute-power';

    public function load(ObjectManager $manager): void
    {
        $attributes = [
            [
                'code' => 'color',
                'name' => '颜色',
                'type' => AttributeType::SALES,
                'valueType' => AttributeValueType::SINGLE,
                'inputType' => AttributeInputType::SELECT,
                'unit' => null,
                'isRequired' => true,
                'isSearchable' => true,
                'isFilterable' => true,
                'isMultiple' => false,
                'sortOrder' => 1,
                'config' => ['show_color' => true],
                'validationRules' => null,
                'status' => AttributeStatus::ACTIVE,
                'remark' => '商品颜色属性',
                'reference' => self::ATTR_COLOR,
            ],
            [
                'code' => 'size',
                'name' => '尺寸',
                'type' => AttributeType::SALES,
                'valueType' => AttributeValueType::SINGLE,
                'inputType' => AttributeInputType::SELECT,
                'unit' => null,
                'isRequired' => true,
                'isSearchable' => true,
                'isFilterable' => true,
                'isMultiple' => false,
                'sortOrder' => 2,
                'config' => null,
                'validationRules' => null,
                'status' => AttributeStatus::ACTIVE,
                'remark' => '商品尺寸属性',
                'reference' => self::ATTR_SIZE,
            ],
            [
                'code' => 'material',
                'name' => '材质',
                'type' => AttributeType::NON_SALES,
                'valueType' => AttributeValueType::SINGLE,
                'inputType' => AttributeInputType::SELECT,
                'unit' => null,
                'isRequired' => false,
                'isSearchable' => true,
                'isFilterable' => true,
                'isMultiple' => false,
                'sortOrder' => 3,
                'config' => null,
                'validationRules' => null,
                'status' => AttributeStatus::ACTIVE,
                'remark' => '商品材质属性',
                'reference' => self::ATTR_MATERIAL,
            ],
            [
                'code' => 'brand',
                'name' => '品牌',
                'type' => AttributeType::NON_SALES,
                'valueType' => AttributeValueType::TEXT,
                'inputType' => AttributeInputType::INPUT,
                'unit' => null,
                'isRequired' => false,
                'isSearchable' => true,
                'isFilterable' => true,
                'isMultiple' => false,
                'sortOrder' => 4,
                'config' => null,
                'validationRules' => ['max_length' => 50],
                'status' => AttributeStatus::ACTIVE,
                'remark' => '商品品牌属性',
                'reference' => self::ATTR_BRAND,
            ],
            [
                'code' => 'weight',
                'name' => '重量',
                'type' => AttributeType::NON_SALES,
                'valueType' => AttributeValueType::NUMBER,
                'inputType' => AttributeInputType::NUMBER,
                'unit' => 'kg',
                'isRequired' => false,
                'isSearchable' => false,
                'isFilterable' => true,
                'isMultiple' => false,
                'sortOrder' => 5,
                'config' => ['min' => 0, 'max' => 1000, 'decimal_places' => 2],
                'validationRules' => ['numeric' => true, 'min' => 0],
                'status' => AttributeStatus::ACTIVE,
                'remark' => '商品重量属性',
                'reference' => self::ATTR_WEIGHT,
            ],
            [
                'code' => 'power',
                'name' => '功率',
                'type' => AttributeType::CUSTOM,
                'valueType' => AttributeValueType::NUMBER,
                'inputType' => AttributeInputType::NUMBER,
                'unit' => 'W',
                'isRequired' => false,
                'isSearchable' => false,
                'isFilterable' => true,
                'isMultiple' => false,
                'sortOrder' => 6,
                'config' => ['min' => 0, 'max' => 10000, 'decimal_places' => 0],
                'validationRules' => ['numeric' => true, 'min' => 0],
                'status' => AttributeStatus::ACTIVE,
                'remark' => '电器功率属性',
                'reference' => self::ATTR_POWER,
            ],
        ];

        foreach ($attributes as $data) {
            $attribute = new Attribute();
            $attribute->setCode($data['code']);
            $attribute->setName($data['name']);
            $attribute->setType($data['type']);
            $attribute->setValueType($data['valueType']);
            $attribute->setInputType($data['inputType']);
            $attribute->setUnit($data['unit']);
            $attribute->setIsRequired($data['isRequired']);
            $attribute->setIsSearchable($data['isSearchable']);
            $attribute->setIsFilterable($data['isFilterable']);
            $attribute->setIsMultiple($data['isMultiple']);
            $attribute->setSortOrder($data['sortOrder']);
            $attribute->setConfig($data['config']);
            $attribute->setValidationRules($data['validationRules']);
            $attribute->setStatus($data['status']);
            $attribute->setRemark($data['remark']);

            $manager->persist($attribute);
            $this->addReference($data['reference'], $attribute);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AttributeGroupFixtures::class,
        ];
    }
}
