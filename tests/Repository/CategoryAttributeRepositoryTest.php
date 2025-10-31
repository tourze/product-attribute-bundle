<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\CatalogBundle\Entity\CatalogType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Repository\CategoryAttributeRepository;

/**
 * @internal
 */
#[CoversClass(CategoryAttributeRepository::class)]
#[RunTestsInSeparateProcesses]
class CategoryAttributeRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return CategoryAttributeRepository::class;
    }

    protected function getEntityClass(): string
    {
        return CategoryAttribute::class;
    }

    protected function getRepository(): CategoryAttributeRepository
    {
        return self::getService(CategoryAttributeRepository::class);
    }

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase required method
    }

    protected function createNewEntity(): CategoryAttribute
    {
        // 创建必要的关联实体
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

        // 创建CatalogType（Catalog需要type字段）
        $catalogType = new CatalogType();
        $catalogType->setName('Test Type');
        $catalogType->setCode('test_type_' . uniqid());
        self::getEntityManager()->persist($catalogType);

        // 创建Catalog实体
        $catalog = new Catalog();
        $catalog->setName('Test Catalog');
        $catalog->setType($catalogType);
        self::getEntityManager()->persist($catalog);

        self::getEntityManager()->flush();

        // 创建CategoryAttribute实体
        $categoryAttribute = new CategoryAttribute();
        $categoryAttribute->setCategory($catalog);
        $categoryAttribute->setAttribute($attribute);
        $categoryAttribute->setGroup($attributeGroup);
        $categoryAttribute->setIsRequired(false);
        $categoryAttribute->setSortOrder(10);
        $categoryAttribute->setIsVisible(true);

        return $categoryAttribute;
    }

    public function testFindByCategoryWithInheritance(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $category = $entity->getCategory();
        $this->assertNotNull($category);
        $result = $this->getRepository()->findByCategoryWithInheritance($category);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(CategoryAttribute::class, $result);
    }

    public function testFindByCategory(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $category = $entity->getCategory();
        $this->assertNotNull($category);
        $result = $this->getRepository()->findByCategory($category);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(CategoryAttribute::class, $result);
    }

    public function testFindSalesAttributesByCategory(): void
    {
        $entity = $this->createNewEntity();
        // 确保是销售属性
        $attribute = $entity->getAttribute();
        $this->assertNotNull($attribute);
        $attribute->setType(AttributeType::SALES);
        self::getEntityManager()->persist($attribute);
        $this->getRepository()->save($entity);

        $category = $entity->getCategory();
        $this->assertNotNull($category);
        $result = $this->getRepository()->findSalesAttributesByCategory($category);

        $this->assertIsArray($result);
        foreach ($result as $categoryAttribute) {
            $attribute = $categoryAttribute->getAttribute();
            $this->assertNotNull($attribute);
            $this->assertEquals(AttributeType::SALES, $attribute->getType());
        }
    }

    public function testFindRequiredAttributesByCategory(): void
    {
        $entity = $this->createNewEntity();
        $entity->setIsRequired(true);
        $this->getRepository()->save($entity);

        $category = $entity->getCategory();
        $this->assertNotNull($category);
        $result = $this->getRepository()->findRequiredAttributesByCategory($category);

        $this->assertIsArray($result);
        foreach ($result as $categoryAttribute) {
            $attribute = $categoryAttribute->getAttribute();
            $this->assertNotNull($attribute);
            $this->assertTrue(true === $categoryAttribute->getIsRequired() || $attribute->isRequired());
        }
    }

    public function testBatchAssociate(): void
    {
        // 创建测试类目类型
        $catalogType = new CatalogType();
        $catalogType->setName('Batch Test Type');
        $catalogType->setCode('batch_type_' . uniqid());
        self::getEntityManager()->persist($catalogType);

        // 创建测试类目
        $catalog = new Catalog();
        $catalog->setName('Batch Test Catalog');
        $catalog->setType($catalogType);
        self::getEntityManager()->persist($catalog);

        // 创建测试属性组
        $group = new AttributeGroup();
        $group->setCode('batch_group_' . uniqid());
        $group->setName('Batch Group');
        $group->setStatus(AttributeStatus::ACTIVE);
        self::getEntityManager()->persist($group);

        // 创建测试属性
        $attribute = new Attribute();
        $attribute->setCode('batch_attr_' . uniqid());
        $attribute->setName('Batch Attribute');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::TEXT);
        $attribute->setInputType(AttributeInputType::INPUT);
        $attribute->setStatus(AttributeStatus::ACTIVE);
        self::getEntityManager()->persist($attribute);

        self::getEntityManager()->flush();

        $attributesData = [
            [
                'attribute' => $attribute,
                'group' => $group,
                'isRequired' => true,
                'defaultValue' => 'test_default',
                'allowedValues' => ['option1', 'option2'],
                'sortOrder' => 10,
                'isVisible' => true,
            ],
        ];

        $result = $this->getRepository()->batchAssociate($catalog, $attributesData);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(CategoryAttribute::class, $result[0]);
        $resultCategory = $result[0]->getCategory();
        $resultAttribute = $result[0]->getAttribute();
        $this->assertNotNull($resultCategory);
        $this->assertNotNull($resultAttribute);
        $this->assertEquals($catalog->getId(), $resultCategory->getId());
        $this->assertEquals($attribute->getId(), $resultAttribute->getId());
    }

    public function testSave(): void
    {
        $entity = $this->createNewEntity();

        $this->getRepository()->save($entity);

        $this->assertNotNull($entity->getId());

        $found = $this->getRepository()->find($entity->getId());
        $this->assertInstanceOf(CategoryAttribute::class, $found);
    }

    public function testRemoveAllByCategory(): void
    {
        $entity = $this->createNewEntity();
        $this->getRepository()->save($entity);

        $catalog = $entity->getCategory();
        $this->assertNotNull($catalog);

        // 确认有数据
        $before = $this->getRepository()->findByCategory($catalog);
        $this->assertNotEmpty($before);

        $this->getRepository()->removeAllByCategory($catalog);

        $after = $this->getRepository()->findByCategory($catalog);
        $this->assertEmpty($after);
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
}
