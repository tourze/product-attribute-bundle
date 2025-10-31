<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Entity\SkuAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Repository\SkuAttributeRepository;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(SkuAttributeRepository::class)]
#[RunTestsInSeparateProcesses]
class SkuAttributeRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return SkuAttributeRepository::class;
    }

    protected function getEntityClass(): string
    {
        return SkuAttribute::class;
    }

    protected function getRepository(): SkuAttributeRepository
    {
        return self::getService(SkuAttributeRepository::class);
    }

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase required method
    }

    protected function createNewEntity(): SkuAttribute
    {
        // 创建必要的关联实体
        $spu = new Spu();
        $spu->setGtin('test_spu_' . uniqid());
        $spu->setTitle('Test SPU');
        self::getEntityManager()->persist($spu);

        $sku = new Sku();
        $sku->setGtin('test_sku_' . uniqid());
        $sku->setUnit('个');
        $sku->setSpu($spu);
        self::getEntityManager()->persist($sku);

        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        $attributeGroup->setStatus(AttributeStatus::ACTIVE);
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        $attribute->setStatus(AttributeStatus::ACTIVE);
        self::getEntityManager()->persist($attribute);

        $attributeValue = new AttributeValue();
        $attributeValue->setCode('test_value_' . uniqid());
        $attributeValue->setValue('Test Value');
        $attributeValue->setAttribute($attribute);
        self::getEntityManager()->persist($attributeValue);

        self::getEntityManager()->flush();

        $skuAttribute = new SkuAttribute();
        $skuAttribute->setSku($sku);
        $skuAttribute->setAttribute($attribute);
        $skuAttribute->setValue('Test SKU Attribute Value');
        $skuAttribute->setName($attribute->getName());

        return $skuAttribute;
    }

    protected function getFirstSku(): ?Sku
    {
        $result = self::getEntityManager()
            ->getRepository(Sku::class)
            ->createQueryBuilder('s')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Sku ? $result : null;
    }

    protected function getFirstAttribute(): ?Attribute
    {
        $result = self::getEntityManager()
            ->getRepository(Attribute::class)
            ->createQueryBuilder('a')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Attribute ? $result : null;
    }

    protected function getFirstEntity(): ?SkuAttribute
    {
        $result = $this->getRepository()
            ->createQueryBuilder('ska')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof SkuAttribute ? $result : null;
    }

    public function testFindBySku(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $sku = $entity->getSku();
        $this->assertNotNull($sku);
        $result = $this->getRepository()->findBySku($sku);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(SkuAttribute::class, $result);
    }

    public function testFindBySkuAndName(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $sku = $entity->getSku();
        $this->assertNotNull($sku);
        $result = $this->getRepository()->findBySkuAndName($sku, $entity->getName());

        $this->assertInstanceOf(SkuAttribute::class, $result);
        $this->assertEquals($entity->getName(), $result->getName());

        $notFound = $this->getRepository()->findBySkuAndName($sku, 'non_existing_name');
        $this->assertNull($notFound);
    }

    public function testFindBySkuAndAttribute(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $sku = $entity->getSku();
        $attribute = $entity->getAttribute();
        $this->assertNotNull($sku);
        $this->assertNotNull($attribute);
        $result = $this->getRepository()->findBySkuAndAttribute($sku, $attribute);

        $this->assertInstanceOf(SkuAttribute::class, $result);
        $resultAttribute = $result->getAttribute();
        $this->assertNotNull($resultAttribute);
        $this->assertEquals($attribute->getId(), $resultAttribute->getId());
    }

    public function testSave(): void
    {
        $entity = $this->createNewEntity();

        $this->getRepository()->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->getRepository()->find($entity->getId());
        $this->assertInstanceOf(SkuAttribute::class, $found);
    }

    public function testRemove(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $id = $entity->getId();
        $this->getRepository()->remove($entity);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testRemoveAllBySku(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $sku = $entity->getSku();
        $this->assertNotNull($sku);

        // 确认有数据
        $before = $this->getRepository()->findBySku($sku);
        $this->assertNotEmpty($before);

        $this->getRepository()->removeAllBySku($sku);

        $after = $this->getRepository()->findBySku($sku);
        $this->assertEmpty($after);
    }

    public function testIsAttributeCombinationUnique(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $attribute = $entity->getAttribute();
        $this->assertNotNull($attribute);
        $attributeValuePairs = [
            [
                'attribute' => $attribute,
                'value' => $entity->getValue(),
            ],
        ];

        // 已存在的组合不唯一
        $this->assertFalse($this->getRepository()->isAttributeCombinationUnique($attributeValuePairs));

        // 排除自身后应该是唯一的
        $sku = $entity->getSku();
        $this->assertNotNull($sku);
        $this->assertTrue($this->getRepository()->isAttributeCombinationUnique($attributeValuePairs, $sku->getId()));

        // 不存在的组合应该是唯一的
        $uniquePairs = [
            [
                'attribute' => $entity->getAttribute(),
                'value' => 'unique_value_' . uniqid(),
            ],
        ];
        $this->assertTrue($this->getRepository()->isAttributeCombinationUnique($uniquePairs));
    }

    public function testBatchSave(): void
    {
        // Create a test entity first to ensure we have a SKU and attribute
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $sku = $entity->getSku();
        $this->assertNotNull($sku, 'SKU should exist for batch save test');

        $attribute = $entity->getAttribute();
        $this->assertNotNull($attribute, 'Attribute should exist for batch save test');

        $attributesData = [
            [
                'attribute' => $attribute,
                'value' => 'batch_test_value_1',
            ],
        ];

        $result = $this->getRepository()->batchSave($sku, $attributesData);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        foreach ($result as $skuAttribute) {
            $this->assertInstanceOf(SkuAttribute::class, $skuAttribute);
            $this->assertSame($sku, $skuAttribute->getSku());
            $this->assertSame($attribute, $skuAttribute->getAttribute());
        }
    }

    public function testFindSkuByAttributeValues(): void
    {
        // Create a test entity first to ensure we have data
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $sku = $entity->getSku();
        $this->assertNotNull($sku, 'SKU should be associated');

        $attribute = $entity->getAttribute();
        $this->assertNotNull($attribute, 'Attribute should be associated');

        $attributeValuePairs = [
            [
                'attribute' => $attribute,
                'value' => $entity->getValue(),
            ],
        ];

        $foundSku = $this->getRepository()->findSkuByAttributeValues($attributeValuePairs);
        $this->assertNotNull($foundSku, 'SKU should be found by attribute values');
        $this->assertSame($sku->getId(), $foundSku->getId());

        // Test with non-existent attribute value combination
        $nonExistentPairs = [
            [
                'attribute' => $attribute,
                'value' => 'non_existent_value_' . uniqid(),
            ],
        ];

        $notFoundSku = $this->getRepository()->findSkuByAttributeValues($nonExistentPairs);
        $this->assertNull($notFoundSku, 'SKU should not be found for non-existent attribute values');

        // Test with empty array
        $emptySku = $this->getRepository()->findSkuByAttributeValues([]);
        $this->assertNull($emptySku, 'SKU should not be found for empty attribute values');
    }
}
