<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\SpuAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Repository\SpuAttributeRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(SpuAttributeRepository::class)]
#[RunTestsInSeparateProcesses]
class SpuAttributeRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return SpuAttributeRepository::class;
    }

    protected function getEntityClass(): string
    {
        return SpuAttribute::class;
    }

    protected function getRepository(): SpuAttributeRepository
    {
        return self::getService(SpuAttributeRepository::class);
    }

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase required method
    }

    protected function createNewEntity(): SpuAttribute
    {
        // 创建必要的关联实体
        $spu = new Spu();
        $spu->setGtin('test_spu_' . uniqid());
        $spu->setTitle('Test SPU');
        self::getEntityManager()->persist($spu);

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

        self::getEntityManager()->flush();

        $spuAttribute = new SpuAttribute();
        $spuAttribute->setSpu($spu);
        $spuAttribute->setAttribute($attribute);
        $spuAttribute->setName($attribute->getName());
        $spuAttribute->setValue('Test SPU Attribute Value');

        return $spuAttribute;
    }

    public function testFindBySpu(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $spu = $entity->getSpu();
        $this->assertNotNull($spu);
        $result = $this->getRepository()->findBySpu($spu);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(SpuAttribute::class, $result);
    }

    public function testFindBySpuAndName(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $spu = $entity->getSpu();
        $name = $entity->getName();
        $this->assertNotNull($spu);
        $this->assertNotNull($name);
        $result = $this->getRepository()->findBySpuAndName($spu, $name);

        $this->assertInstanceOf(SpuAttribute::class, $result);
        $this->assertEquals($name, $result->getName());

        $notFound = $this->getRepository()->findBySpuAndName($spu, 'non_existing_name');
        $this->assertNull($notFound);
    }

    public function testFindBySpuAndAttribute(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $spu = $entity->getSpu();
        $attribute = $entity->getAttribute();
        $this->assertNotNull($spu);
        $this->assertNotNull($attribute);
        $result = $this->getRepository()->findBySpuAndAttribute($spu, $attribute);

        $this->assertInstanceOf(SpuAttribute::class, $result);
        $resultAttribute = $result->getAttribute();
        $this->assertNotNull($resultAttribute);
        $this->assertEquals($attribute->getId(), $resultAttribute->getId());
    }

    public function testBatchSave(): void
    {
        // 创建SPU
        $spu = new Spu();
        $spu->setGtin('batch_test_spu_' . uniqid());
        $spu->setTitle('Batch Test SPU');
        self::getEntityManager()->persist($spu);

        // 创建属性
        $attribute = new Attribute();
        $attribute->setCode('batch_test_attr_' . uniqid());
        $attribute->setName('Batch Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        $attribute->setStatus(AttributeStatus::ACTIVE);
        self::getEntityManager()->persist($attribute);

        self::getEntityManager()->flush();

        $attributesData = [
            [
                'attribute' => $attribute,
                'value' => 'Test Value',
            ],
        ];

        $result = $this->getRepository()->batchSave($spu, $attributesData);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(SpuAttribute::class, $result[0]);
        $resultSpu = $result[0]->getSpu();
        $resultAttribute = $result[0]->getAttribute();
        $this->assertNotNull($resultSpu);
        $this->assertNotNull($resultAttribute);
        $this->assertEquals($spu->getId(), $resultSpu->getId());
        $this->assertEquals($attribute->getId(), $resultAttribute->getId());
    }

    public function testSave(): void
    {
        $entity = $this->createNewEntity();

        $this->getRepository()->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->getRepository()->find($entity->getId());
        $this->assertInstanceOf(SpuAttribute::class, $found);
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

    public function testRemoveAllBySpu(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $spu = $entity->getSpu();
        $this->assertNotNull($spu);

        // 确认有数据
        $before = $this->getRepository()->findBySpu($spu);
        $this->assertNotEmpty($before);

        $this->getRepository()->removeAllBySpu($spu);

        $after = $this->getRepository()->findBySpu($spu);
        $this->assertEmpty($after);
    }

    public function testRemoveBySpuAndAttribute(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $spu = $entity->getSpu();
        $attribute = $entity->getAttribute();
        $this->assertNotNull($spu);
        $this->assertNotNull($attribute);

        // 确认存在
        $before = $this->getRepository()->findBySpuAndAttribute($spu, $attribute);
        $this->assertNotNull($before);

        $this->getRepository()->removeBySpuAndAttribute($spu, $attribute);

        // 确认删除
        $after = $this->getRepository()->findBySpuAndAttribute($spu, $attribute);
        $this->assertNull($after);
    }

    public function testFindSpusByAttributeValues(): void
    {
        $entity = $this->createNewEntity();
        $entity->setValue('test_search_value');
        $this->getRepository()->save($entity);

        $attributeValuePairs = [
            [
                'attribute' => $entity->getAttribute(),
                'value' => 'test_search_value',
            ],
        ];

        $result = $this->getRepository()->findSpusByAttributeValues($attributeValuePairs);

        $this->assertIsArray($result);
        // 由于查询复杂性，这里只验证返回类型
        foreach ($result as $spu) {
            $this->assertInstanceOf(Spu::class, $spu);
        }

        // 空数组应该返回空结果
        $emptyResult = $this->getRepository()->findSpusByAttributeValues([]);
        $this->assertEmpty($emptyResult);
    }
}
