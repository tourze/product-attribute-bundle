<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(Attribute::class)]
class AttributeTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new Attribute();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'code' => ['code', 'test_code'],
            'name' => ['name', 'Test Attribute'],
            'type' => ['type', AttributeType::SALES],
            'valueType' => ['valueType', AttributeValueType::SINGLE],
            'inputType' => ['inputType', AttributeInputType::SELECT],
            'sortOrder' => ['sortOrder', 10],
            'status' => ['status', AttributeStatus::ACTIVE],
            'unit' => ['unit', '个'],
            'config' => ['config', ['key' => 'value']],
            'validationRules' => ['validationRules', ['rule' => 'required']],
            'description' => ['description', 'Test description'],
            'remark' => ['remark', 'Test remark'],
        ];
    }

    public function testAttributeCreation(): void
    {
        $attribute = new Attribute();

        $this->assertNull($attribute->getId());
        $this->assertEquals('', $attribute->getCode());
        $this->assertEquals('', $attribute->getName());
        $this->assertEquals(AttributeType::NON_SALES, $attribute->getType());
        $this->assertEquals(AttributeValueType::TEXT, $attribute->getValueType());
        $this->assertEquals(AttributeInputType::INPUT, $attribute->getInputType());
        $this->assertFalse($attribute->isRequired());
        $this->assertFalse($attribute->isSearchable());
        $this->assertFalse($attribute->isFilterable());
        $this->assertEquals(0, $attribute->getSortOrder());
        $this->assertEquals(AttributeStatus::ACTIVE, $attribute->getStatus());
    }

    public function testAttributeSetters(): void
    {
        $attribute = new Attribute();

        $testCode = 'test_attr_' . uniqid();
        $attribute->setCode($testCode);
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::MULTIPLE);
        $attribute->setInputType(AttributeInputType::SELECT);
        $attribute->setIsRequired(true);
        $attribute->setIsSearchable(true);
        $attribute->setIsFilterable(true);
        $attribute->setSortOrder(10);
        $attribute->setStatus(AttributeStatus::INACTIVE);

        $this->assertEquals($testCode, $attribute->getCode());
        $this->assertEquals('Test Attribute', $attribute->getName());
        $this->assertEquals(AttributeType::SALES, $attribute->getType());
        $this->assertEquals(AttributeValueType::MULTIPLE, $attribute->getValueType());
        $this->assertEquals(AttributeInputType::SELECT, $attribute->getInputType());
        $this->assertTrue($attribute->isRequired());
        $this->assertTrue($attribute->isSearchable());
        $this->assertTrue($attribute->isFilterable());
        $this->assertEquals(10, $attribute->getSortOrder());
        $this->assertEquals(AttributeStatus::INACTIVE, $attribute->getStatus());
    }

    public function testIsEnumType(): void
    {
        $attribute = new Attribute();

        // 测试枚举类型
        $attribute->setValueType(AttributeValueType::SINGLE);
        $this->assertTrue($attribute->isEnumType());

        $attribute->setValueType(AttributeValueType::MULTIPLE);
        $this->assertTrue($attribute->isEnumType());

        // 测试非枚举类型
        $attribute->setValueType(AttributeValueType::TEXT);
        $this->assertFalse($attribute->isEnumType());

        $attribute->setValueType(AttributeValueType::NUMBER);
        $this->assertFalse($attribute->isEnumType());

        $attribute->setValueType(AttributeValueType::DATE);
        $this->assertFalse($attribute->isEnumType());

        $attribute->setValueType(AttributeValueType::BOOLEAN);
        $this->assertFalse($attribute->isEnumType());
    }

    public function testIsActive(): void
    {
        $attribute = new Attribute();

        $attribute->setStatus(AttributeStatus::ACTIVE);
        $this->assertTrue($attribute->isActive());

        $attribute->setStatus(AttributeStatus::INACTIVE);
        $this->assertFalse($attribute->isActive());

        $attribute->setStatus(AttributeStatus::INACTIVE);
        $this->assertFalse($attribute->isActive());
    }

    public function testStringRepresentation(): void
    {
        $attribute = new Attribute();

        // 测试默认值
        $this->assertEquals('', (string) $attribute);

        // 测试有名称时
        $attribute->setName('Test Attribute');
        $this->assertEquals('Test Attribute', (string) $attribute);

        // 测试只有编码时
        $attribute = new Attribute();
        $testCode = 'test_code_' . uniqid();
        $attribute->setCode($testCode);
        $this->assertEquals($testCode, (string) $attribute);

        // 测试名称优先
        $attribute->setName('Test Name');
        $this->assertEquals('Test Name', (string) $attribute);
    }
}
