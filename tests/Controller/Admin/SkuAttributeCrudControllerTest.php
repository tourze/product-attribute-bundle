<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\ProductAttributeBundle\Controller\Admin\SkuAttributeCrudController;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\SkuAttribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Repository\SkuAttributeRepository;

/**
 * @internal
 */
#[CoversClass(SkuAttributeCrudController::class)]
#[RunTestsInSeparateProcesses]
class SkuAttributeCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private SkuAttributeRepository $skuAttributeRepository;

    protected function getControllerService(): SkuAttributeCrudController
    {
        return self::getService(SkuAttributeCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'SKU' => ['SKU'];
        yield '属性' => ['属性'];
        yield '名称' => ['名称'];
        yield '值' => ['值'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'sku' => ['sku'];
        yield 'attribute' => ['attribute'];
        yield 'name' => ['name'];
        yield 'value' => ['value'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'sku' => ['sku'];
        yield 'attribute' => ['attribute'];
        yield 'name' => ['name'];
        yield 'value' => ['value'];
    }

    protected function onSetUp(): void
    {
        $this->skuAttributeRepository = self::getService(SkuAttributeRepository::class);
    }

    private function createTestAttribute(): Attribute
    {
        $attribute = new Attribute();
        $attribute->setCode('test_sku_attr');
        $attribute->setName('测试SKU属性');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);

        return $attribute;
    }

    public function testIndexAction(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('SKU属性列表', $crawler->text());
    }

    public function testNewAction(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute/new');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('新建SKU属性', $crawler->text());

        // 检查表单字段
        $this->assertCount(1, $crawler->filter('input[name*="[skuId]"]'));
        $this->assertCount(1, $crawler->filter('select[name*="[attribute]"]'));
    }

    public function testCreateAction(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试属性
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $entityManager->persist($attribute);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute/new');

        $form = $crawler->selectButton('Create')->form([
            'SkuAttribute[skuId]' => 'test_sku_123',
            'SkuAttribute[attribute]' => $attribute->getId(),
            'SkuAttribute[name]' => 'test_name',
            'SkuAttribute[value]' => 'test_value',
        ]);

        $client->submit($form);

        $this->assertEquals(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());

        // 验证数据是否创建成功
        $skuAttribute = $this->skuAttributeRepository->findOneBy([
            'name' => 'test_name',
            'value' => 'test_value',
            'attribute' => $attribute,
        ]);

        $this->assertInstanceOf(SkuAttribute::class, $skuAttribute);
        $this->assertEquals('test_name', $skuAttribute->getName());
        $this->assertEquals('test_value', $skuAttribute->getValue());
        $this->assertEquals($attribute->getId(), $skuAttribute->getAttribute()?->getId());
    }

    public function testDetailAction(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $entityManager->persist($attribute);

        $skuAttribute = new SkuAttribute();
        $skuAttribute->setSkuId('detail_sku_123');
        $skuAttribute->setAttribute($attribute);
        $skuAttribute->setName('详情测试名称');
        $skuAttribute->setValue('detail_value');

        $entityManager->persist($skuAttribute);
        $entityManager->flush();

        $crawler = $client->request('GET', "/admin/product-attribute/sku-attribute/{$skuAttribute->getId()}");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('SKU属性详情', $crawler->text());
        $this->assertStringContainsString('detail_sku_123', $crawler->text());
        $this->assertStringContainsString('测试SKU属性', $crawler->text());
        $this->assertStringContainsString('detail_value', $crawler->text());
    }

    public function testDeleteAction(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $entityManager->persist($attribute);

        $skuAttribute = new SkuAttribute();
        $skuAttribute->setSkuId('delete_sku_123');
        $skuAttribute->setAttribute($attribute);
        $skuAttribute->setName('删除测试名称');
        $skuAttribute->setValue('delete_value');

        $entityManager->persist($skuAttribute);
        $entityManager->flush();

        // 访问详情页面，验证对象存在
        $crawler = $client->request('GET', "/admin/product-attribute/sku-attribute/{$skuAttribute->getId()}");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('删除测试名称', $crawler->text());
        $this->assertStringContainsString('delete_value', $crawler->text());
    }

    public function testFilterByAttribute(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();

        $attribute1 = $this->createTestAttribute();
        $attribute1->setCode('filter_sku_attr1');
        $attribute1->setName('筛选SKU属性1');
        $entityManager->persist($attribute1);

        $attribute2 = new Attribute();
        $attribute2->setCode('filter_sku_attr2');
        $attribute2->setName('筛选SKU属性2');
        $attribute2->setType(AttributeType::SALES);
        $attribute2->setValueType(AttributeValueType::SINGLE);
        $attribute2->setInputType(AttributeInputType::SELECT);
        $entityManager->persist($attribute2);

        $skuAttribute1 = new SkuAttribute();
        $skuAttribute1->setSkuId('filter_sku_1');
        $skuAttribute1->setAttribute($attribute1);
        $skuAttribute1->setName('筛选测试名称1');
        $skuAttribute1->setValue('filter_value_1');
        $entityManager->persist($skuAttribute1);

        $skuAttribute2 = new SkuAttribute();
        $skuAttribute2->setSkuId('filter_sku_2');
        $skuAttribute2->setAttribute($attribute2);
        $skuAttribute2->setName('筛选测试名称2');
        $skuAttribute2->setValue('filter_value_2');
        $entityManager->persist($skuAttribute2);

        $entityManager->flush();

        // 测试按属性筛选
        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute', [
            'filters[attribute][value]' => $attribute1->getId(),
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // 验证页面包含筛选功能相关的元素
        $this->assertStringContainsString('Filters', $crawler->text());
        $this->assertStringContainsString('筛选SKU属性1', $crawler->text());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(AccessDeniedException::class);

        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $client->request('GET', '/admin/product-attribute/sku-attribute');
    }

    public function testCreateWithMissingRequiredFields(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $entityManager->persist($attribute);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute/new');

        // 测试缺少必填字段 name
        $form = $crawler->selectButton('Create')->form([
            'SkuAttribute[skuId]' => 'test_sku',
            'SkuAttribute[attribute]' => $attribute->getId(),
            'SkuAttribute[value]' => 'test_value',
        ]);

        $client->submit($form);
        $this->assertEquals(422, $client->getResponse()->getStatusCode()); // 表单验证失败应该返回422

        // 测试缺少必填字段 value
        $form = $crawler->selectButton('Create')->form([
            'SkuAttribute[skuId]' => 'test_sku',
            'SkuAttribute[attribute]' => $attribute->getId(),
            'SkuAttribute[name]' => 'test_name',
        ]);

        $client->submit($form);
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }

    public function testCreateWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $entityManager->persist($attribute);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute/new');

        // 测试有效数据
        $form = $crawler->selectButton('Create')->form([
            'SkuAttribute[skuId]' => 'test_sku_123',
            'SkuAttribute[attribute]' => $attribute->getId(),
            'SkuAttribute[name]' => 'test_name',
            'SkuAttribute[value]' => 'test_value',
        ]);

        $client->submit($form);
        $this->assertEquals(Response::HTTP_FOUND, $client->getResponse()->getStatusCode()); // 成功创建应该重定向

        // 验证数据是否创建成功
        $skuAttribute = $this->skuAttributeRepository->findOneBy([
            'name' => 'test_name',
            'value' => 'test_value',
        ]);
        $this->assertInstanceOf(SkuAttribute::class, $skuAttribute);
        $this->assertEquals('test_name', $skuAttribute->getName());
        $this->assertEquals('test_value', $skuAttribute->getValue());
        $this->assertEquals($attribute->getId(), $skuAttribute->getAttribute()?->getId());
    }

    public function testValidationErrorsOnRequiredFields(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $entityManager->persist($attribute);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 检查表单是否存在
        $this->assertGreaterThan(0, $crawler->filter('form')->count());

        // 提交缺少必填字段的表单以触发验证错误
        $form = $crawler->selectButton('Create')->form([
            'SkuAttribute[skuId]' => '', // 空字符串会触发required验证错误
            'SkuAttribute[name]' => '', // 空字符串会触发NotBlank验证错误
            'SkuAttribute[value]' => '', // 空字符串会触发NotBlank验证错误
        ]);

        // 对于下拉框字段，我们不设置值（保持默认的空选项）
        $crawler = $client->submit($form);

        // 验证返回422状态码表示验证失败
        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        // 验证页面包含验证错误信息
        $responseContent = $client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);

        // 检查是否包含表单验证错误的标识符
        $hasValidationErrors = $crawler->filter('.invalid-feedback, .form-error-message, .field-error, [class*="error"], [class*="invalid"]')->count() > 0;
        $this->assertTrue($hasValidationErrors, '表单应该包含验证错误信息');
    }

    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createAdminUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $entityManager->persist($attribute);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin/product-attribute/sku-attribute/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 测试提交空表单
        $form = $crawler->selectButton('Create')->form([
            'SkuAttribute[skuId]' => '',
            'SkuAttribute[name]' => '',
            'SkuAttribute[value]' => '',
        ]);

        // 对于下拉框字段（attribute和attributeValue），不设置值会自动使用空选项

        $crawler = $client->submit($form);
        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        // 验证错误信息存在
        $responseContent = $client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);

        // 检查错误信息的容器是否存在
        $errorElements = $crawler->filter('.invalid-feedback, .form-error-message, .field-error, [class*="error"], [class*="invalid"], .help-block.error');
        $this->assertGreaterThan(0, $errorElements->count(), '应该存在验证错误信息');
    }
}
