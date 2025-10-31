<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\ProductAttributeBundle\Enum\AttributeType;

/**
 * @internal
 */
#[CoversClass(AttributeType::class)]
class AttributeTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('sales', AttributeType::SALES->value);
        $this->assertEquals('non_sales', AttributeType::NON_SALES->value);
        $this->assertEquals('custom', AttributeType::CUSTOM->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('销售属性', AttributeType::SALES->label());
        $this->assertEquals('非销售属性', AttributeType::NON_SALES->label());
        $this->assertEquals('自定义属性', AttributeType::CUSTOM->label());
    }

    public function testCasesCount(): void
    {
        $cases = AttributeType::cases();
        $this->assertCount(3, $cases);
    }

    public function testFromValue(): void
    {
        $this->assertEquals(AttributeType::SALES, AttributeType::from('sales'));
        $this->assertEquals(AttributeType::NON_SALES, AttributeType::from('non_sales'));
        $this->assertEquals(AttributeType::CUSTOM, AttributeType::from('custom'));
    }

    public function testTryFromValue(): void
    {
        $this->assertEquals(AttributeType::SALES, AttributeType::tryFrom('sales'));
        $this->assertEquals(AttributeType::NON_SALES, AttributeType::tryFrom('non_sales'));
        $this->assertEquals(AttributeType::CUSTOM, AttributeType::tryFrom('custom'));
        $this->assertNull(AttributeType::tryFrom('invalid_value'));
        $this->assertNull(AttributeType::tryFrom(''));
        $this->assertNull(AttributeType::tryFrom('product'));
    }

    public function testAllCasesHaveLabels(): void
    {
        foreach (AttributeType::cases() as $case) {
            $this->assertNotEmpty($case->label(), "Case {$case->value} should have a non-empty label");
        }
    }

    public function testLabelsAreUnique(): void
    {
        $labels = [];
        foreach (AttributeType::cases() as $case) {
            $label = $case->label();
            $this->assertNotContains($label, $labels, "Label '{$label}' is duplicated");
            $labels[] = $label;
        }
    }

    public function testTypeComparison(): void
    {
        $salesType1 = AttributeType::SALES;
        $salesType2 = AttributeType::SALES;
        $nonSalesType = AttributeType::NON_SALES;

        $this->assertEquals($salesType1, $salesType2);
        $this->assertNotEquals($salesType1, $nonSalesType);
        $this->assertSame($salesType1, $salesType2);
    }

    public function testValueStringification(): void
    {
        $this->assertEquals('sales', (string) AttributeType::SALES->value);
        $this->assertEquals('non_sales', (string) AttributeType::NON_SALES->value);
        $this->assertEquals('custom', (string) AttributeType::CUSTOM->value);
    }

    public function testToArray(): void
    {
        $result = AttributeType::SALES->toSelectItem();
        $this->assertIsArray($result);

        // 验证返回的数据结构
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertIsString($result['value']);
        $this->assertIsString($result['label']);
        $this->assertIsString($result['text']);

        $this->assertEquals('sales', $result['value']);
        $this->assertEquals('销售属性', $result['label']);
    }
}
