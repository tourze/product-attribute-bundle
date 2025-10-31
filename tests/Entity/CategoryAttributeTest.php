<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(CategoryAttribute::class)]
class CategoryAttributeTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new CategoryAttribute();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'defaultValue' => ['defaultValue', 'test_default'],
            'allowedValues' => ['allowedValues', ['value1', 'value2']],
            'sortOrder' => ['sortOrder', 10],
            'config' => ['config', ['key' => 'value']],
        ];
    }

    public function testCategoryAttributeCreation(): void
    {
        $categoryAttribute = new CategoryAttribute();

        $this->assertNull($categoryAttribute->getId());
        $this->assertNull($categoryAttribute->getCategory());
        $this->assertNull($categoryAttribute->getAttribute());
        $this->assertNull($categoryAttribute->getGroup());
        $this->assertNull($categoryAttribute->getIsRequired());
        $this->assertTrue($categoryAttribute->isVisible());
        $this->assertNull($categoryAttribute->getDefaultValue());
        $this->assertNull($categoryAttribute->getAllowedValues());
        $this->assertSame(0, $categoryAttribute->getSortOrder());
        $this->assertNull($categoryAttribute->getConfig());
        $this->assertFalse($categoryAttribute->isInherited());
    }

    public function testCategoryAttributeSetters(): void
    {
        $categoryAttribute = new CategoryAttribute();

        $attribute = new Attribute();
        $attribute->setCode('test_attr');
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);

        $group = new AttributeGroup();
        $group->setCode('test_group');
        $group->setName('Test Group');

        $categoryAttribute->setAttribute($attribute);
        $categoryAttribute->setGroup($group);
        $categoryAttribute->setIsRequired(true);
        $categoryAttribute->setIsVisible(false);
        $categoryAttribute->setDefaultValue('default');
        $categoryAttribute->setAllowedValues(['value1', 'value2']);
        $categoryAttribute->setSortOrder(10);
        $categoryAttribute->setConfig(['key' => 'value']);
        $categoryAttribute->setIsInherited(true);

        $this->assertSame($attribute, $categoryAttribute->getAttribute());
        $this->assertSame($group, $categoryAttribute->getGroup());
        $this->assertTrue($categoryAttribute->getIsRequired());
        $this->assertFalse($categoryAttribute->isVisible());
        $this->assertSame('default', $categoryAttribute->getDefaultValue());
        $this->assertSame(['value1', 'value2'], $categoryAttribute->getAllowedValues());
        $this->assertSame(10, $categoryAttribute->getSortOrder());
        $this->assertSame(['key' => 'value'], $categoryAttribute->getConfig());
        $this->assertTrue($categoryAttribute->isInherited());
    }

    public function testAttributeRelation(): void
    {
        $categoryAttribute = new CategoryAttribute();
        $attribute = new Attribute();
        $attribute->setCode('color');
        $attribute->setName('颜色');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);

        $categoryAttribute->setAttribute($attribute);

        $this->assertSame($attribute, $categoryAttribute->getAttribute());
    }

    public function testGroupRelation(): void
    {
        $categoryAttribute = new CategoryAttribute();
        $group = new AttributeGroup();
        $group->setCode('test_group');
        $group->setName('Test Group');

        $categoryAttribute->setGroup($group);

        $this->assertSame($group, $categoryAttribute->getGroup());
    }

    public function testConfigHandling(): void
    {
        $categoryAttribute = new CategoryAttribute();

        $this->assertNull($categoryAttribute->getConfig());

        $config = ['setting1' => 'value1', 'setting2' => 'value2'];
        $categoryAttribute->setConfig($config);
        $this->assertSame($config, $categoryAttribute->getConfig());

        $categoryAttribute->setConfig(null);
        $this->assertNull($categoryAttribute->getConfig());
    }

    public function testAllowedValuesHandling(): void
    {
        $categoryAttribute = new CategoryAttribute();

        $this->assertNull($categoryAttribute->getAllowedValues());

        $allowedValues = ['red', 'green', 'blue'];
        $categoryAttribute->setAllowedValues($allowedValues);
        $this->assertSame($allowedValues, $categoryAttribute->getAllowedValues());

        $categoryAttribute->setAllowedValues(null);
        $this->assertNull($categoryAttribute->getAllowedValues());
    }
}
