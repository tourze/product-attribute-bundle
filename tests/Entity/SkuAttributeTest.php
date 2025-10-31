<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\SkuAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(SkuAttribute::class)]
class SkuAttributeTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new SkuAttribute();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'skuId' => ['skuId', 'test_sku_id'],
        ];
    }

    public function testSkuAttributeCreation(): void
    {
        $skuAttribute = new SkuAttribute();

        $this->assertNull($skuAttribute->getId());
        $this->assertInstanceOf(SkuAttribute::class, $skuAttribute);
    }

    public function testBasicFunctionality(): void
    {
        $skuAttribute = new SkuAttribute();

        // Test that the entity can be created and is an instance of the correct class
        $this->assertInstanceOf(SkuAttribute::class, $skuAttribute);
        $this->assertNull($skuAttribute->getId(), 'New SkuAttribute should not have ID yet');
    }

    public function testAttributeRelation(): void
    {
        $skuAttribute = new SkuAttribute();
        $attribute = new Attribute();
        $attribute->setCode('size');
        $attribute->setName('Size');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);

        // Test basic attribute creation
        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertInstanceOf(SkuAttribute::class, $skuAttribute);
        $this->assertEquals('size', $attribute->getCode());
        $this->assertEquals('Size', $attribute->getName());
    }
}
