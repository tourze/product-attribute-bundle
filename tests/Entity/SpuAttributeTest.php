<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\SpuAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(SpuAttribute::class)]
class SpuAttributeTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new SpuAttribute();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'spuId' => ['spuId', 'test_spu_id'],
            'valueIds' => ['valueIds', ['value1', 'value2']],
            'valueText' => ['valueText', 'test_value'],
        ];
    }

    public function testSpuAttributeCreation(): void
    {
        $spuAttribute = new SpuAttribute();

        $this->assertNull($spuAttribute->getId());
        $this->assertInstanceOf(SpuAttribute::class, $spuAttribute);
    }

    public function testBasicFunctionality(): void
    {
        $spuAttribute = new SpuAttribute();

        // Test that the entity can be created and is an instance of the correct class
        $this->assertInstanceOf(SpuAttribute::class, $spuAttribute);
        $this->assertNull($spuAttribute->getId(), 'New SpuAttribute should not have ID yet');
    }

    public function testAttributeRelation(): void
    {
        $spuAttribute = new SpuAttribute();
        $attribute = new Attribute();
        $attribute->setCode('brand');
        $attribute->setName('Brand');
        $attribute->setType(AttributeType::NON_SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);

        // Test basic attribute creation
        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertInstanceOf(SpuAttribute::class, $spuAttribute);
        $this->assertEquals('brand', $attribute->getCode());
        $this->assertEquals('Brand', $attribute->getName());
    }
}
