<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

/**
 * @internal
 */
#[CoversClass(AttributeGroup::class)]
class AttributeGroupTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new AttributeGroup();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'code' => ['code', 'test_group'],
            'name' => ['name', 'Test Group'],
            'description' => ['description', 'Test description'],
            'icon' => ['icon', 'test-icon'],
            'sortOrder' => ['sortOrder', 10],
        ];
    }

    public function testAttributeGroupCreation(): void
    {
        $group = new AttributeGroup();

        $this->assertNull($group->getId());
        $this->assertSame('', $group->getCode());
        $this->assertSame('', $group->getName());
        $this->assertNull($group->getIcon());
        $this->assertTrue($group->isExpanded());
        $this->assertSame(0, $group->getSortOrder());
        $this->assertSame(AttributeStatus::ACTIVE, $group->getStatus());
        $this->assertInstanceOf(Collection::class, $group->getCategoryAttributes());
        $this->assertCount(0, $group->getCategoryAttributes());
    }

    public function testAttributeGroupSetters(): void
    {
        $group = new AttributeGroup();

        $testCode = 'test_group_' . uniqid();
        $group->setCode($testCode);
        $group->setName('Test Group');
        $group->setIcon('fas fa-palette');
        $group->setIsExpanded(false);
        $group->setSortOrder(10);
        $group->setStatus(AttributeStatus::INACTIVE);

        $this->assertSame($testCode, $group->getCode());
        $this->assertSame('Test Group', $group->getName());
        $this->assertSame('fas fa-palette', $group->getIcon());
        $this->assertFalse($group->isExpanded());
        $this->assertSame(10, $group->getSortOrder());
        $this->assertSame(AttributeStatus::INACTIVE, $group->getStatus());
    }

    public function testIsActive(): void
    {
        $group = new AttributeGroup();

        $group->setStatus(AttributeStatus::ACTIVE);
        $this->assertTrue($group->isActive());

        $group->setStatus(AttributeStatus::INACTIVE);
        $this->assertFalse($group->isActive());
    }

    public function testStringRepresentation(): void
    {
        $group = new AttributeGroup();

        // Test default value
        $this->assertSame('', (string) $group);

        // Test with name
        $group->setName('Test Group');
        $this->assertSame('Test Group', (string) $group);

        // Test with code only (no name)
        $group = new AttributeGroup();
        $testCode = 'test_code_' . uniqid();
        $group->setCode($testCode);
        $this->assertSame($testCode, (string) $group);

        // Test name takes priority over code
        $group->setName('Test Name');
        $this->assertSame('Test Name', (string) $group);
    }

    public function testCategoryAttributeCollection(): void
    {
        $group = new AttributeGroup();

        $collection = $group->getCategoryAttributes();
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);

        // CategoryAttribute management is handled through CategoryAttribute entity
        // not directly through AttributeGroup methods
    }
}
