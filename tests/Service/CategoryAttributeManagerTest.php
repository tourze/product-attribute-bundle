<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;
use Tourze\ProductAttributeBundle\Repository\CategoryAttributeRepository;
use Tourze\ProductAttributeBundle\Service\CategoryAttributeManager;

/**
 * @internal
 */
#[CoversClass(CategoryAttributeManager::class)]
class CategoryAttributeManagerTest extends TestCase
{
    private CategoryAttributeManager $service;

    private EntityManagerInterface&MockObject $entityManager;

    private CategoryAttributeRepository&MockObject $repository;

    private Catalog&MockObject $mockCategory;

    private Attribute&MockObject $mockAttribute;

    private AttributeGroup&MockObject $mockGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CategoryAttributeRepository::class);
        $this->service = new CategoryAttributeManager($this->entityManager, $this->repository);

        $this->createMockData();
    }

    private function createMockData(): void
    {
        $this->mockCategory = $this->createMock(Catalog::class);
        $this->mockCategory->method('getId')->willReturn('test-category-id');

        $this->mockAttribute = $this->createMock(Attribute::class);
        $this->mockAttribute->method('getId')->willReturn('test-attribute-id');
        $this->mockAttribute->method('getName')->willReturn('Test Attribute');

        $this->mockGroup = $this->createMock(AttributeGroup::class);
        $this->mockGroup->method('getId')->willReturn('test-group-id');
    }

    public function testAssociateAttributeCreatesNewAssociation(): void
    {
        // 模拟不存在现有关联
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'category' => $this->mockCategory,
                'attribute' => $this->mockAttribute,
            ])
            ->willReturn(null)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with(self::isInstanceOf(CategoryAttribute::class))
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->associateAttribute(
            $this->mockCategory,
            $this->mockAttribute,
            $this->mockGroup,
            ['isRequired' => true, 'sortOrder' => 10]
        );

        $this->assertInstanceOf(CategoryAttribute::class, $result);
    }

    public function testAssociateAttributeUpdatesExistingAssociation(): void
    {
        $existingCategoryAttribute = $this->createMock(CategoryAttribute::class);

        // 模拟存在现有关联
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'category' => $this->mockCategory,
                'attribute' => $this->mockAttribute,
            ])
            ->willReturn($existingCategoryAttribute)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->associateAttribute(
            $this->mockCategory,
            $this->mockAttribute,
            $this->mockGroup,
            ['isRequired' => true]
        );

        $this->assertSame($existingCategoryAttribute, $result);
    }

    public function testBatchAssociate(): void
    {
        $attributesData = [
            [
                'attribute' => $this->mockAttribute,
                'group' => $this->mockGroup,
                'isRequired' => true,
                'sortOrder' => 5,
            ],
            [
                'attribute' => $this->mockAttribute,
                'isVisible' => false,
            ],
        ];

        // 模拟不存在现有关联
        $this->repository
            ->method('findOneBy')
            ->willReturn(null)
        ;

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
        ;

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush')
        ;

        $result = $this->service->batchAssociate($this->mockCategory, $attributesData);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(CategoryAttribute::class, $result);
    }

    public function testUpdateCategoryAttribute(): void
    {
        $categoryAttribute = $this->createMock(CategoryAttribute::class);
        $data = ['isRequired' => false, 'sortOrder' => 20];

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->updateCategoryAttribute($categoryAttribute, $data);

        $this->assertSame($categoryAttribute, $result);
    }

    public function testDissociateAttributeWithExistingAssociation(): void
    {
        $categoryAttribute = $this->createMock(CategoryAttribute::class);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'category' => $this->mockCategory,
                'attribute' => $this->mockAttribute,
            ])
            ->willReturn($categoryAttribute)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($categoryAttribute)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $this->service->dissociateAttribute($this->mockCategory, $this->mockAttribute);
    }

    public function testDissociateAttributeWithNoExistingAssociation(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'category' => $this->mockCategory,
                'attribute' => $this->mockAttribute,
            ])
            ->willReturn(null)
        ;

        $this->entityManager
            ->expects($this->never())
            ->method('remove')
        ;

        $this->entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $this->service->dissociateAttribute($this->mockCategory, $this->mockAttribute);
    }

    public function testAssociateAttributeWithMinimalOptions(): void
    {
        $this->repository
            ->method('findOneBy')
            ->willReturn(null)
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->associateAttribute($this->mockCategory, $this->mockAttribute);

        $this->assertInstanceOf(CategoryAttribute::class, $result);
    }

    public function testCopyAttributesToCategory(): void
    {
        $targetCategory = $this->createMock(Catalog::class);
        $targetCategory->method('getId')->willReturn('target-category-id');

        $sourceCategoryAttribute = $this->createMock(CategoryAttribute::class);
        $sourceCategoryAttribute->method('getAttribute')->willReturn($this->mockAttribute);
        $sourceCategoryAttribute->method('getGroup')->willReturn($this->mockGroup);
        $sourceCategoryAttribute->method('getIsRequired')->willReturn(true);
        $sourceCategoryAttribute->method('isVisible')->willReturn(true);
        $sourceCategoryAttribute->method('getDefaultValue')->willReturn('default');
        $sourceCategoryAttribute->method('getAllowedValues')->willReturn(['value1', 'value2']);
        $sourceCategoryAttribute->method('getSortOrder')->willReturn(10);
        $sourceCategoryAttribute->method('getConfig')->willReturn(['key' => 'value']);

        // 模拟从源类目获取属性
        $this->repository
            ->expects($this->once())
            ->method('findByCategory')
            ->with($this->mockCategory)
            ->willReturn([$sourceCategoryAttribute])
        ;

        // 期望持久化新的类目属性关联
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with(self::isInstanceOf(CategoryAttribute::class))
        ;

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->copyAttributesToCategory($this->mockCategory, $targetCategory);

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(CategoryAttribute::class, $result);
    }

    public function testCopyAttributesToCategoryWithEmptySource(): void
    {
        $targetCategory = $this->createMock(Catalog::class);
        $targetCategory->method('getId')->willReturn('target-category-id');

        // 模拟源类目没有属性
        $this->repository
            ->expects($this->once())
            ->method('findByCategory')
            ->with($this->mockCategory)
            ->willReturn([])
        ;

        // 不应该调用持久化操作
        $this->entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        // 即使没有属性，flush仍然会被调用（这是Service的实现逻辑）
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->copyAttributesToCategory($this->mockCategory, $targetCategory);

        $this->assertCount(0, $result);
    }

    public function testDissociateAllAttributes(): void
    {
        // 模拟调用repository的removeAllByCategory方法
        $this->repository
            ->expects($this->once())
            ->method('removeAllByCategory')
            ->with($this->mockCategory)
        ;

        $this->service->dissociateAllAttributes($this->mockCategory);
    }

    public function testBasicOperations(): void
    {
        $service = new CategoryAttributeManager(
            $this->entityManager,
            $this->repository
        );
        $this->assertInstanceOf(CategoryAttributeManager::class, $service);
    }
}
