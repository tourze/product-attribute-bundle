<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(AttributeValueType::class)]
class AttributeValueTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('single', AttributeValueType::SINGLE->value);
        $this->assertEquals('multiple', AttributeValueType::MULTIPLE->value);
        $this->assertEquals('text', AttributeValueType::TEXT->value);
        $this->assertEquals('number', AttributeValueType::NUMBER->value);
        $this->assertEquals('date', AttributeValueType::DATE->value);
        $this->assertEquals('boolean', AttributeValueType::BOOLEAN->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('单选', AttributeValueType::SINGLE->label());
        $this->assertEquals('多选', AttributeValueType::MULTIPLE->label());
        $this->assertEquals('文本', AttributeValueType::TEXT->label());
        $this->assertEquals('数字', AttributeValueType::NUMBER->label());
        $this->assertEquals('日期', AttributeValueType::DATE->label());
        $this->assertEquals('布尔值', AttributeValueType::BOOLEAN->label());
    }

    public function testIsEnum(): void
    {
        $this->assertTrue(AttributeValueType::SINGLE->isEnum());
        $this->assertTrue(AttributeValueType::MULTIPLE->isEnum());
        $this->assertFalse(AttributeValueType::TEXT->isEnum());
        $this->assertFalse(AttributeValueType::NUMBER->isEnum());
        $this->assertFalse(AttributeValueType::DATE->isEnum());
        $this->assertFalse(AttributeValueType::BOOLEAN->isEnum());
    }

    public function testValueComparison(): void
    {
        $singleType1 = AttributeValueType::SINGLE;
        $singleType2 = AttributeValueType::SINGLE;
        $textType = AttributeValueType::TEXT;

        $this->assertEquals($singleType1, $singleType2);
        $this->assertNotEquals($singleType1, $textType);
        $this->assertSame($singleType1, $singleType2);
    }

    public function testValueStringification(): void
    {
        $this->assertEquals('single', (string) AttributeValueType::SINGLE->value);
        $this->assertEquals('text', (string) AttributeValueType::TEXT->value);
        $this->assertEquals('boolean', (string) AttributeValueType::BOOLEAN->value);
    }

    public function testToArray(): void
    {
        $result = AttributeValueType::SINGLE->toSelectItem();
        $this->assertIsArray($result);

        // 验证返回的数据结构
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertIsString($result['value']);
        $this->assertIsString($result['label']);
        $this->assertIsString($result['text']);

        $this->assertEquals('single', $result['value']);
        $this->assertEquals('单选', $result['label']);
    }
}
