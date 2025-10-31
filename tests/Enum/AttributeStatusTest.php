<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

/**
 * @internal
 */
#[CoversClass(AttributeStatus::class)]
class AttributeStatusTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('active', AttributeStatus::ACTIVE->value);
        $this->assertEquals('inactive', AttributeStatus::INACTIVE->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('启用', AttributeStatus::ACTIVE->label());
        $this->assertEquals('禁用', AttributeStatus::INACTIVE->label());
    }

    public function testIsActive(): void
    {
        $this->assertTrue(AttributeStatus::ACTIVE->isActive());
        $this->assertFalse(AttributeStatus::INACTIVE->isActive());
    }

    public function testCasesCount(): void
    {
        $cases = AttributeStatus::cases();
        $this->assertCount(2, $cases);
    }

    public function testFromValue(): void
    {
        $this->assertEquals(AttributeStatus::ACTIVE, AttributeStatus::from('active'));
        $this->assertEquals(AttributeStatus::INACTIVE, AttributeStatus::from('inactive'));
    }

    public function testTryFromValue(): void
    {
        $this->assertEquals(AttributeStatus::ACTIVE, AttributeStatus::tryFrom('active'));
        $this->assertEquals(AttributeStatus::INACTIVE, AttributeStatus::tryFrom('inactive'));
        $this->assertNull(AttributeStatus::tryFrom('invalid_value'));
        $this->assertNull(AttributeStatus::tryFrom(''));
        $this->assertNull(AttributeStatus::tryFrom('deleted'));
    }

    public function testAllCasesHaveLabels(): void
    {
        foreach (AttributeStatus::cases() as $case) {
            $this->assertNotEmpty($case->label(), "Case {$case->value} should have a non-empty label");
        }
    }

    public function testLabelsAreUnique(): void
    {
        $labels = [];
        foreach (AttributeStatus::cases() as $case) {
            $label = $case->label();
            $this->assertNotContains($label, $labels, "Label '{$label}' is duplicated");
            $labels[] = $label;
        }
    }

    public function testStatusTransitions(): void
    {
        // 测试状态反转
        $activeStatus = AttributeStatus::ACTIVE;
        $this->assertTrue($activeStatus->isActive());

        $inactiveStatus = AttributeStatus::INACTIVE;
        $this->assertFalse($inactiveStatus->isActive());
    }

    public function testStatusComparison(): void
    {
        $status1 = AttributeStatus::ACTIVE;
        $status2 = AttributeStatus::ACTIVE;
        $status3 = AttributeStatus::INACTIVE;

        $this->assertEquals($status1, $status2);
        $this->assertNotEquals($status1, $status3);
        $this->assertSame($status1, $status2);
    }

    public function testValueStringification(): void
    {
        $this->assertEquals('active', (string) AttributeStatus::ACTIVE->value);
        $this->assertEquals('inactive', (string) AttributeStatus::INACTIVE->value);
    }

    public function testToArray(): void
    {
        $result = AttributeStatus::ACTIVE->toSelectItem();
        $this->assertIsArray($result);

        // 验证返回的数据结构
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertIsString($result['value']);
        $this->assertIsString($result['label']);
        $this->assertIsString($result['text']);

        $this->assertEquals('active', $result['value']);
        $this->assertEquals('启用', $result['label']);
    }
}
