<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Repository\AttributeValueRepository;

/**
 * @internal
 */
#[CoversClass(AttributeValueRepository::class)]
#[RunTestsInSeparateProcesses]
class AttributeValueRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return AttributeValueRepository::class;
    }

    protected function getEntityClass(): string
    {
        return AttributeValue::class;
    }

    protected function getRepository(): AttributeValueRepository
    {
        return self::getService(AttributeValueRepository::class);
    }

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase required method
    }

    protected function createNewEntity(): object
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);

        self::getEntityManager()->flush();

        $attributeValue = new AttributeValue();
        $attributeValue->setCode('test_value_' . uniqid());
        $attributeValue->setValue('Test Value');
        $attributeValue->setAttribute($attribute);

        return $attributeValue;
    }

    public function testFindActiveValuesByAttribute(): void
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);

        $activeValue = new AttributeValue();
        $activeValue->setCode('active_value');
        $activeValue->setValue('Active Value');
        $activeValue->setAttribute($attribute);
        $activeValue->setStatus(AttributeStatus::ACTIVE);
        self::getEntityManager()->persist($activeValue);

        $inactiveValue = new AttributeValue();
        $inactiveValue->setCode('inactive_value');
        $inactiveValue->setValue('Inactive Value');
        $inactiveValue->setAttribute($attribute);
        $inactiveValue->setStatus(AttributeStatus::INACTIVE);
        self::getEntityManager()->persist($inactiveValue);

        self::getEntityManager()->flush();

        $result = $this->getRepository()->findActiveValuesByAttribute($attribute);

        $this->assertCount(1, $result);
        $this->assertEquals('active_value', $result[0]->getCode());
    }

    public function testFindByAttributeAndCode(): void
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);

        $attributeValue = new AttributeValue();
        $attributeValue->setCode('test_code');
        $attributeValue->setValue('Test Value');
        $attributeValue->setAttribute($attribute);
        self::getEntityManager()->persist($attributeValue);

        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByAttributeAndCode($attribute, 'test_code');

        $this->assertInstanceOf(AttributeValue::class, $result);
        $this->assertEquals('test_code', $result->getCode());

        $nullResult = $this->getRepository()->findByAttributeAndCode($attribute, 'non_existing_code');
        $this->assertNull($nullResult);
    }

    public function testFindByAttributeAndValue(): void
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);

        $attributeValue = new AttributeValue();
        $attributeValue->setCode('test_code');
        $attributeValue->setValue('Test Value');
        $attributeValue->setAttribute($attribute);
        self::getEntityManager()->persist($attributeValue);

        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByAttributeAndValue($attribute, 'Test Value');

        $this->assertInstanceOf(AttributeValue::class, $result);
        $this->assertEquals('Test Value', $result->getValue());

        $nullResult = $this->getRepository()->findByAttributeAndValue($attribute, 'Non Existing Value');
        $this->assertNull($nullResult);
    }

    public function testFindByIds(): void
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);

        $value1 = new AttributeValue();
        $value1->setCode('value1');
        $value1->setValue('Value 1');
        $value1->setAttribute($attribute);
        self::getEntityManager()->persist($value1);

        $value2 = new AttributeValue();
        $value2->setCode('value2');
        $value2->setValue('Value 2');
        $value2->setAttribute($attribute);
        self::getEntityManager()->persist($value2);

        self::getEntityManager()->flush();

        $ids = [$value1->getId(), $value2->getId()];
        $ids = array_filter($ids, fn ($id) => null !== $id);
        $ids = array_map('intval', $ids);

        $result = $this->getRepository()->findByIds($ids);

        $this->assertCount(2, $result);

        $emptyResult = $this->getRepository()->findByIds([]);
        $this->assertEmpty($emptyResult);
    }

    public function testSave(): void
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);
        self::getEntityManager()->flush();

        $attributeValue = new AttributeValue();
        $attributeValue->setCode('save_test');
        $attributeValue->setValue('Save Test Value');
        $attributeValue->setAttribute($attribute);

        $this->getRepository()->save($attributeValue);

        $this->assertNotNull($attributeValue->getId());

        $found = $this->getRepository()->find($attributeValue->getId());
        $this->assertInstanceOf(AttributeValue::class, $found);
        $this->assertEquals('save_test', $found->getCode());
    }

    public function testRemove(): void
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);

        $attributeValue = new AttributeValue();
        $attributeValue->setCode('remove_test');
        $attributeValue->setValue('Remove Test Value');
        $attributeValue->setAttribute($attribute);
        self::getEntityManager()->persist($attributeValue);
        self::getEntityManager()->flush();

        $id = $attributeValue->getId();
        $this->getRepository()->remove($attributeValue);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testBatchCreate(): void
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('test_group_' . uniqid());
        $attributeGroup->setName('Test Group');
        self::getEntityManager()->persist($attributeGroup);

        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('Test Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        self::getEntityManager()->persist($attribute);
        self::getEntityManager()->flush();

        $valuesData = [
            ['code' => 'value1', 'value' => 'Value 1'],
            ['code' => 'value2', 'value' => 'Value 2'],
            ['code' => 'value3', 'value' => 'Value 3'],
        ];

        $result = $this->getRepository()->batchCreate($attribute, $valuesData);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $index => $attributeValue) {
            $this->assertInstanceOf(AttributeValue::class, $attributeValue);
            $this->assertEquals($valuesData[$index]['code'], $attributeValue->getCode());
            $this->assertEquals($valuesData[$index]['value'], $attributeValue->getValue());
            $this->assertNotNull($attributeValue->getAttribute());
            $this->assertEquals($attribute->getId(), $attributeValue->getAttribute()->getId());
        }

        // 测试空数组
        $emptyResult = $this->getRepository()->batchCreate($attribute, []);
        $this->assertEmpty($emptyResult);
    }
}
