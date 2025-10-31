<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Repository;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;

/**
 * @internal
 */
#[CoversClass(AttributeRepository::class)]
#[RunTestsInSeparateProcesses]
class AttributeRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepositoryClass(): string
    {
        return AttributeRepository::class;
    }

    protected function getEntityClass(): string
    {
        return Attribute::class;
    }

    protected function getRepository(): AttributeRepository
    {
        return self::getService(AttributeRepository::class);
    }

    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase required method
    }

    protected function createNewEntity(): Attribute
    {
        $attribute = new Attribute();
        $attribute->setCode('test_attr_' . uniqid());
        $attribute->setName('测试属性 ' . uniqid());
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);
        $attribute->setStatus(AttributeStatus::ACTIVE);

        return $attribute;
    }

    public function testSave(): void
    {
        $attribute = $this->createNewEntity();

        $this->getRepository()->save($attribute);

        $this->assertNotNull($attribute->getId());

        $found = $this->getRepository()->find($attribute->getId());
        $this->assertInstanceOf(Attribute::class, $found);
        $this->assertEquals($attribute->getCode(), $found->getCode());
    }

    public function testRemove(): void
    {
        $attribute = $this->createNewEntity();
        $this->getRepository()->save($attribute);
        $id = $attribute->getId();

        $this->getRepository()->remove($attribute);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testIsCodeExists(): void
    {
        $uniqueCode = 'unique_code_test_' . uniqid();
        $attribute = new Attribute();
        $attribute->setCode($uniqueCode);
        $attribute->setName('唯一编码测试');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);

        $this->getRepository()->save($attribute);

        $this->assertTrue($this->getRepository()->isCodeExists($uniqueCode));
        $this->assertFalse($this->getRepository()->isCodeExists('non_existent_code'));

        // 测试排除自身
        $this->assertFalse($this->getRepository()->isCodeExists($uniqueCode, $attribute->getId()));
    }

    public function testFindByType(): void
    {
        // 创建不同类型的属性
        $salesAttr = new Attribute();
        $salesAttr->setCode('sales_attr_test_' . uniqid());
        $salesAttr->setName('销售属性测试');
        $salesAttr->setType(AttributeType::SALES);
        $salesAttr->setValueType(AttributeValueType::SINGLE);
        $salesAttr->setInputType(AttributeInputType::SELECT);

        $nonSalesAttr = new Attribute();
        $nonSalesAttr->setCode('non_sales_attr_test_' . uniqid());
        $nonSalesAttr->setName('非销售属性测试');
        $nonSalesAttr->setType(AttributeType::NON_SALES);
        $nonSalesAttr->setValueType(AttributeValueType::TEXT);
        $nonSalesAttr->setInputType(AttributeInputType::INPUT);

        $this->getRepository()->save($salesAttr, true);
        $this->getRepository()->save($nonSalesAttr, true);

        $salesAttributes = $this->getRepository()->findByType(AttributeType::SALES);
        $this->assertNotEmpty($salesAttributes);

        foreach ($salesAttributes as $attr) {
            $this->assertEquals(AttributeType::SALES, $attr->getType());
        }
    }

    public function testFindActiveAttributes(): void
    {
        $activeAttr = new Attribute();
        $activeAttr->setCode('active_attr_test_' . uniqid());
        $activeAttr->setName('激活属性测试');
        $activeAttr->setType(AttributeType::SALES);
        $activeAttr->setValueType(AttributeValueType::SINGLE);
        $activeAttr->setInputType(AttributeInputType::SELECT);
        $activeAttr->setStatus(AttributeStatus::ACTIVE);

        $inactiveAttr = new Attribute();
        $inactiveAttr->setCode('inactive_attr_test_' . uniqid());
        $inactiveAttr->setName('非激活属性测试');
        $inactiveAttr->setType(AttributeType::NON_SALES);
        $inactiveAttr->setValueType(AttributeValueType::TEXT);
        $inactiveAttr->setInputType(AttributeInputType::INPUT);
        $inactiveAttr->setStatus(AttributeStatus::INACTIVE);

        $this->getRepository()->save($activeAttr, true);
        $this->getRepository()->save($inactiveAttr, true);

        $activeAttributes = $this->getRepository()->findActiveAttributes();
        $this->assertNotEmpty($activeAttributes);

        foreach ($activeAttributes as $attr) {
            $this->assertEquals(AttributeStatus::ACTIVE, $attr->getStatus());
        }
    }

    public function testFindSalesAttributes(): void
    {
        $salesAttr = new Attribute();
        $salesAttr->setCode('sales_attr_test2_' . uniqid());
        $salesAttr->setName('销售属性测试');
        $salesAttr->setType(AttributeType::SALES);
        $salesAttr->setValueType(AttributeValueType::SINGLE);
        $salesAttr->setInputType(AttributeInputType::SELECT);
        $salesAttr->setStatus(AttributeStatus::ACTIVE);

        $nonSalesAttr = new Attribute();
        $nonSalesAttr->setCode('non_sales_attr_test2_' . uniqid());
        $nonSalesAttr->setName('非销售属性测试');
        $nonSalesAttr->setType(AttributeType::NON_SALES);
        $nonSalesAttr->setValueType(AttributeValueType::TEXT);
        $nonSalesAttr->setInputType(AttributeInputType::INPUT);
        $nonSalesAttr->setStatus(AttributeStatus::ACTIVE);

        $this->getRepository()->save($salesAttr, true);
        $this->getRepository()->save($nonSalesAttr, true);

        $salesAttributes = $this->getRepository()->findSalesAttributes();
        $this->assertNotEmpty($salesAttributes);

        foreach ($salesAttributes as $attr) {
            $this->assertEquals(AttributeType::SALES, $attr->getType());
        }
    }

    public function testFindNonSalesAttributes(): void
    {
        $salesAttr = new Attribute();
        $salesAttr->setCode('sales_attr_test2');
        $salesAttr->setName('销售属性测试2');
        $salesAttr->setType(AttributeType::SALES);
        $salesAttr->setValueType(AttributeValueType::SINGLE);
        $salesAttr->setInputType(AttributeInputType::SELECT);
        $salesAttr->setStatus(AttributeStatus::ACTIVE);

        $nonSalesAttr = new Attribute();
        $nonSalesAttr->setCode('non_sales_attr_test2');
        $nonSalesAttr->setName('非销售属性测试2');
        $nonSalesAttr->setType(AttributeType::NON_SALES);
        $nonSalesAttr->setValueType(AttributeValueType::TEXT);
        $nonSalesAttr->setInputType(AttributeInputType::INPUT);
        $nonSalesAttr->setStatus(AttributeStatus::ACTIVE);

        $this->getRepository()->save($salesAttr, true);
        $this->getRepository()->save($nonSalesAttr, true);

        $nonSalesAttributes = $this->getRepository()->findNonSalesAttributes();
        $this->assertNotEmpty($nonSalesAttributes);

        foreach ($nonSalesAttributes as $attr) {
            $this->assertEquals(AttributeType::NON_SALES, $attr->getType());
        }
    }

    public function testFindByCode(): void
    {
        $testCode = 'find_by_code_test_' . uniqid();
        $attribute = new Attribute();
        $attribute->setCode($testCode);
        $attribute->setName('查找编码测试');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);

        $this->getRepository()->save($attribute);

        $found = $this->getRepository()->findByCode($testCode);
        $this->assertInstanceOf(Attribute::class, $found);
        $this->assertEquals($testCode, $found->getCode());

        $notFound = $this->getRepository()->findByCode('non_existent_code');
        $this->assertNull($notFound);
    }

    public function testFindByCodes(): void
    {
        $code1 = 'codes_test_1_' . uniqid();
        $code2 = 'codes_test_2_' . uniqid();
        $code3 = 'codes_test_3_' . uniqid();

        $attr1 = new Attribute();
        $attr1->setCode($code1);
        $attr1->setName('编码测试1');
        $attr1->setType(AttributeType::SALES);
        $attr1->setValueType(AttributeValueType::SINGLE);
        $attr1->setInputType(AttributeInputType::SELECT);

        $attr2 = new Attribute();
        $attr2->setCode($code2);
        $attr2->setName('编码测试2');
        $attr2->setType(AttributeType::NON_SALES);
        $attr2->setValueType(AttributeValueType::TEXT);
        $attr2->setInputType(AttributeInputType::INPUT);

        $attr3 = new Attribute();
        $attr3->setCode($code3);
        $attr3->setName('编码测试3');
        $attr3->setType(AttributeType::CUSTOM);
        $attr3->setValueType(AttributeValueType::SINGLE);
        $attr3->setInputType(AttributeInputType::SELECT);

        $this->getRepository()->save($attr1, true);
        $this->getRepository()->save($attr2, true);
        $this->getRepository()->save($attr3, true);

        $found = $this->getRepository()->findByCodes([$code1, $code3]);
        $this->assertCount(2, $found);

        $codes = array_map(fn ($attr) => $attr->getCode(), $found);
        $this->assertContains($code1, $codes);
        $this->assertContains($code3, $codes);
        $this->assertNotContains($code2, $codes);

        $emptyResult = $this->getRepository()->findByCodes([]);
        $this->assertEmpty($emptyResult);
    }

    public function testCreateSearchQueryBuilder(): void
    {
        $qb = $this->getRepository()->createSearchQueryBuilder();
        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $qbWithSearch = $this->getRepository()->createSearchQueryBuilder('test');
        $this->assertInstanceOf(QueryBuilder::class, $qbWithSearch);
    }
}
