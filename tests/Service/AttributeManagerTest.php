<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Exception\AttributeException;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;
use Tourze\ProductAttributeBundle\Repository\AttributeValueRepository;
use Tourze\ProductAttributeBundle\Service\AttributeManager;

/**
 * @internal
 */
#[CoversClass(AttributeManager::class)]
class AttributeManagerTest extends TestCase
{
    private AttributeManager $attributeManager;

    private MockObject&EntityManagerInterface $entityManager;

    private MockObject&AttributeRepository $attributeRepository;

    private MockObject&AttributeValueRepository $attributeValueRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->attributeRepository = $this->createMock(AttributeRepository::class);
        $this->attributeValueRepository = $this->createMock(AttributeValueRepository::class);

        $this->attributeManager = new AttributeManager(
            $this->entityManager,
            $this->attributeRepository,
            $this->attributeValueRepository
        );
    }

    public function testCreateAttributeSuccess(): void
    {
        $data = [
            'code' => 'color',
            'name' => '颜色',
            'type' => AttributeType::SALES,
        ];

        $this->attributeRepository
            ->expects($this->once())
            ->method('isCodeExists')
            ->with('color')
            ->willReturn(false)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $attribute = $this->attributeManager->createAttribute($data);

        $this->assertEquals('color', $attribute->getCode());
        $this->assertEquals('颜色', $attribute->getName());
        $this->assertEquals(AttributeType::SALES, $attribute->getType());
    }

    public function testCreateAttributeWithDuplicateCode(): void
    {
        $data = [
            'code' => 'color',
            'name' => '颜色',
        ];

        $this->attributeRepository
            ->expects($this->once())
            ->method('isCodeExists')
            ->with('color')
            ->willReturn(true)
        ;

        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage('属性编码 "color" 已存在');

        $this->attributeManager->createAttribute($data);
    }

    public function testUpdateAttributeSuccess(): void
    {
        $attribute = new Attribute();
        $attribute->setCode('old_code');
        $attribute->setName('Old Name');

        $data = [
            'code' => 'new_code',
            'name' => 'New Name',
        ];

        $this->attributeRepository
            ->expects($this->once())
            ->method('isCodeExists')
            ->with('new_code', null)
            ->willReturn(false)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $updatedAttribute = $this->attributeManager->updateAttribute($attribute, $data);

        $this->assertEquals('new_code', $updatedAttribute->getCode());
        $this->assertEquals('New Name', $updatedAttribute->getName());
    }

    public function testUpdateAttributeWithDuplicateCode(): void
    {
        $attribute = new Attribute();
        $attribute->setCode('old_code');

        // 模拟 getId 方法返回
        $reflection = new \ReflectionClass($attribute);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($attribute, '123');

        $data = [
            'code' => 'existing_code',
        ];

        $this->attributeRepository
            ->expects($this->once())
            ->method('isCodeExists')
            ->with('existing_code', '123')
            ->willReturn(true)
        ;

        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage('属性编码 "existing_code" 已存在');

        $this->attributeManager->updateAttribute($attribute, $data);
    }

    public function testDeleteAttribute(): void
    {
        $attribute = new Attribute();
        $attribute->setStatus(AttributeStatus::ACTIVE);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $this->attributeManager->deleteAttribute($attribute);

        $this->assertEquals(AttributeStatus::INACTIVE, $attribute->getStatus());
    }

    public function testActivateAttribute(): void
    {
        $attribute = new Attribute();
        $attribute->setStatus(AttributeStatus::INACTIVE);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $this->attributeManager->activateAttribute($attribute);

        $this->assertEquals(AttributeStatus::ACTIVE, $attribute->getStatus());
    }

    public function testAddAttributeValueSuccess(): void
    {
        $attribute = new Attribute();
        $data = [
            'code' => 'red',
            'value' => '红色',
        ];

        $this->attributeValueRepository
            ->expects($this->once())
            ->method('isCodeExists')
            ->with($attribute, 'red')
            ->willReturn(false)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $value = $this->attributeManager->addAttributeValue($attribute, $data);

        $this->assertEquals($attribute, $value->getAttribute());
        $this->assertEquals('red', $value->getCode());
        $this->assertEquals('红色', $value->getValue());
    }

    public function testAddAttributeValueWithDuplicateCode(): void
    {
        $attribute = new Attribute();
        $data = [
            'code' => 'red',
            'value' => '红色',
        ];

        $this->attributeValueRepository
            ->expects($this->once())
            ->method('isCodeExists')
            ->with($attribute, 'red')
            ->willReturn(true)
        ;

        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage('属性值编码 "red" 已存在');

        $this->attributeManager->addAttributeValue($attribute, $data);
    }

    public function testImportAttributeValues(): void
    {
        $attribute = new Attribute();
        $valuesData = [
            ['code' => 'red', 'value' => '红色'],
            ['code' => 'blue', 'value' => '蓝色'],
        ];

        // 第一个值不存在，第二个值已存在
        $this->attributeValueRepository
            ->method('findByAttributeAndCode')
            ->willReturnMap([
                [$attribute, 'red', null],
                [$attribute, 'blue', new AttributeValue()],
            ])
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $values = $this->attributeManager->importAttributeValues($attribute, $valuesData);

        $this->assertCount(2, $values);
        $this->assertInstanceOf(AttributeValue::class, $values[0]);
        $this->assertInstanceOf(AttributeValue::class, $values[1]);
    }

    public function testDeactivateAttribute(): void
    {
        $attribute = new Attribute();
        $attribute->setStatus(AttributeStatus::ACTIVE);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $this->attributeManager->deactivateAttribute($attribute);

        $this->assertEquals(AttributeStatus::INACTIVE, $attribute->getStatus());
    }

    public function testUpdateAttributeValue(): void
    {
        $attribute = new Attribute();
        $value = new AttributeValue();
        $value->setAttribute($attribute);
        $value->setCode('old_code');
        $value->setValue('Old Value');

        // 模拟 getId 方法返回
        $reflection = new \ReflectionClass($value);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($value, '456');

        $data = [
            'code' => 'new_code',
            'value' => 'New Value',
        ];

        $this->attributeValueRepository
            ->expects($this->once())
            ->method('isCodeExists')
            ->with($attribute, 'new_code', '456')
            ->willReturn(false)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $updatedValue = $this->attributeManager->updateAttributeValue($value, $data);

        $this->assertEquals('new_code', $updatedValue->getCode());
        $this->assertEquals('New Value', $updatedValue->getValue());
    }

    public function testDeleteAttributeValue(): void
    {
        $value = new AttributeValue();
        $value->setStatus(AttributeStatus::ACTIVE);

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $this->attributeManager->deleteAttributeValue($value);

        $this->assertEquals(AttributeStatus::INACTIVE, $value->getStatus());
    }
}
