<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(AttributeValue::class)]
class AttributeValueTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new AttributeValue();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'code' => ['code', 'test_code'],
            'value' => ['value', 'Test Value'],
            'colorValue' => ['colorValue', '#FF0000'],
            'imageUrl' => ['imageUrl', 'https://example.com/test.png'],
            'sortOrder' => ['sortOrder', 10],
        ];
    }

    public function testAttributeValueCreation(): void
    {
        $value = new AttributeValue();

        $this->assertNull($value->getId());
        $this->assertSame('', $value->getCode());
        $this->assertSame('', $value->getValue());
        $this->assertNull($value->getAttribute());
        $this->assertNull($value->getAliases());
        $this->assertNull($value->getColorValue());
        $this->assertNull($value->getImageUrl());
        $this->assertSame(0, $value->getSortOrder());
        $this->assertSame(AttributeStatus::ACTIVE, $value->getStatus());
    }

    public function testAttributeValueSetters(): void
    {
        $value = new AttributeValue();

        $value->setCode('red');
        $value->setValue('红色');
        $value->setColorValue('#FF0000');
        $value->setImageUrl('https://example.com/red.png');
        $value->setSortOrder(10);
        $value->setStatus(AttributeStatus::INACTIVE);

        $this->assertSame('red', $value->getCode());
        $this->assertSame('红色', $value->getValue());
        $this->assertSame('#FF0000', $value->getColorValue());
        $this->assertSame('https://example.com/red.png', $value->getImageUrl());
        $this->assertSame(10, $value->getSortOrder());
        $this->assertSame(AttributeStatus::INACTIVE, $value->getStatus());
    }

    public function testIsActive(): void
    {
        $value = new AttributeValue();

        $value->setStatus(AttributeStatus::ACTIVE);
        $this->assertTrue($value->isActive());

        $value->setStatus(AttributeStatus::INACTIVE);
        $this->assertFalse($value->isActive());
    }

    public function testStringRepresentation(): void
    {
        $value = new AttributeValue();

        // Test default value
        $this->assertSame('', (string) $value);

        // Test with value
        $value->setValue('红色');
        $this->assertSame('红色', (string) $value);

        // Test with code only (no value)
        $value = new AttributeValue();
        $value->setCode('red');
        $this->assertSame('red', (string) $value);

        // Test value takes priority over code
        $value->setValue('Red');
        $this->assertSame('Red', (string) $value);
    }

    public function testAttributeRelation(): void
    {
        $attribute = new Attribute();
        $attribute->setCode('color');
        $attribute->setName('颜色');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);

        $value = new AttributeValue();
        $value->setAttribute($attribute);

        $this->assertSame($attribute, $value->getAttribute());
    }

    public function testAliasManagement(): void
    {
        $value = new AttributeValue();
        $value->setValue('红色');

        // Initially no aliases
        $this->assertNull($value->getAliases());

        // Set aliases
        $aliases = ['Red', 'rouge', '赤'];
        $value->setAliases($aliases);
        $this->assertSame($aliases, $value->getAliases());

        // Add alias
        $value->addAlias('crimson');
        $aliases = $value->getAliases();
        $this->assertNotNull($aliases);
        $this->assertContains('crimson', $aliases);
        $this->assertCount(4, $aliases);

        // Add duplicate alias (should not duplicate)
        $value->addAlias('Red');
        $aliases = $value->getAliases();
        $this->assertNotNull($aliases);
        $this->assertCount(4, $aliases);

        // Remove alias
        $value->removeAlias('rouge');
        $aliases = $value->getAliases();
        $this->assertNotNull($aliases);
        $this->assertNotContains('rouge', $aliases);
        $this->assertCount(3, $aliases);

        // Remove all aliases by setting to null
        $value->setAliases(null);
        $this->assertNull($value->getAliases());
    }

    public function testColorValueHandling(): void
    {
        $value = new AttributeValue();

        $this->assertNull($value->getColorValue());

        $value->setColorValue('#FF0000');
        $this->assertSame('#FF0000', $value->getColorValue());

        $value->setColorValue(null);
        $this->assertNull($value->getColorValue());
    }

    public function testImageUrlHandling(): void
    {
        $value = new AttributeValue();

        $this->assertNull($value->getImageUrl());

        $imageUrl = 'https://example.com/image.png';
        $value->setImageUrl($imageUrl);
        $this->assertSame($imageUrl, $value->getImageUrl());

        $value->setImageUrl(null);
        $this->assertNull($value->getImageUrl());
    }
}
