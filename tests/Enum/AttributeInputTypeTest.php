<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;

/**
 * @internal
 */
#[CoversClass(AttributeInputType::class)]
class AttributeInputTypeTest extends AbstractEnumTestCase
{
    public function testLabels(): void
    {
        $this->assertEquals('下拉框', AttributeInputType::SELECT->label());
        $this->assertEquals('复选框', AttributeInputType::CHECKBOX->label());
        $this->assertEquals('单选框', AttributeInputType::RADIO->label());
        $this->assertEquals('输入框', AttributeInputType::INPUT->label());
        $this->assertEquals('文本域', AttributeInputType::TEXTAREA->label());
        $this->assertEquals('日期选择器', AttributeInputType::DATEPICKER->label());
        $this->assertEquals('数字输入框', AttributeInputType::NUMBER->label());
        $this->assertEquals('开关', AttributeInputType::SWITCH->label());
    }

    public function testCasesCount(): void
    {
        $cases = AttributeInputType::cases();
        $this->assertCount(8, $cases);
    }

    public function testFromValue(): void
    {
        $this->assertEquals(AttributeInputType::SELECT, AttributeInputType::from('select'));
        $this->assertEquals(AttributeInputType::CHECKBOX, AttributeInputType::from('checkbox'));
        $this->assertEquals(AttributeInputType::RADIO, AttributeInputType::from('radio'));
        $this->assertEquals(AttributeInputType::INPUT, AttributeInputType::from('input'));
        $this->assertEquals(AttributeInputType::TEXTAREA, AttributeInputType::from('textarea'));
        $this->assertEquals(AttributeInputType::DATEPICKER, AttributeInputType::from('datepicker'));
        $this->assertEquals(AttributeInputType::NUMBER, AttributeInputType::from('number'));
        $this->assertEquals(AttributeInputType::SWITCH, AttributeInputType::from('switch'));
    }

    public function testTryFromValue(): void
    {
        $this->assertEquals(AttributeInputType::SELECT, AttributeInputType::tryFrom('select'));
        $this->assertEquals(AttributeInputType::INPUT, AttributeInputType::tryFrom('input'));
        $this->assertNull(AttributeInputType::tryFrom('invalid_value'));
        $this->assertNull(AttributeInputType::tryFrom(''));
    }

    public function testAllCasesHaveLabels(): void
    {
        foreach (AttributeInputType::cases() as $case) {
            $this->assertNotEmpty($case->label(), "Case {$case->value} should have a non-empty label");
        }
    }

    public function testLabelsAreUnique(): void
    {
        $labels = [];
        foreach (AttributeInputType::cases() as $case) {
            $label = $case->label();
            $this->assertNotContains($label, $labels, "Label '{$label}' is duplicated");
            $labels[] = $label;
        }
    }

    public function testToArray(): void
    {
        $result = AttributeInputType::SELECT->toSelectItem();
        $this->assertIsArray($result);

        // 验证返回的数据结构
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertIsString($result['value']);
        $this->assertIsString($result['label']);
        $this->assertIsString($result['text']);

        $this->assertEquals('select', $result['value']);
        $this->assertEquals('下拉框', $result['label']);
    }
}
