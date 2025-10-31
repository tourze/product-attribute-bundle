<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\ProductAttributeBundle\Controller\AttributeListController;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(AttributeListController::class)]
#[RunTestsInSeparateProcesses]
class AttributeListControllerTest extends AbstractWebTestCase
{
    private function createTestAttribute(): Attribute
    {
        $attribute = new Attribute();
        $attribute->setCode('api_test_attr');
        $attribute->setName('API测试属性');
        $attribute->setType(AttributeType::SALES);
        $attribute->setValueType(AttributeValueType::SINGLE);
        $attribute->setInputType(AttributeInputType::SELECT);
        $attribute->setStatus(AttributeStatus::ACTIVE);

        return $attribute;
    }

    public function testGetAttributesList(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();

        $attribute1 = $this->createTestAttribute();
        $attribute1->setCode('list_attr1');
        $attribute1->setName('列表属性1');
        $entityManager->persist($attribute1);

        $attribute2 = new Attribute();
        $attribute2->setCode('list_attr2');
        $attribute2->setName('列表属性2');
        $attribute2->setType(AttributeType::NON_SALES);
        $attribute2->setValueType(AttributeValueType::TEXT);
        $attribute2->setInputType(AttributeInputType::INPUT);
        $attribute2->setStatus(AttributeStatus::ACTIVE);
        $entityManager->persist($attribute2);

        // 非激活属性不应该在结果中
        $inactiveAttribute = new Attribute();
        $inactiveAttribute->setCode('inactive_attr');
        $inactiveAttribute->setName('非激活属性');
        $inactiveAttribute->setType(AttributeType::SALES);
        $inactiveAttribute->setValueType(AttributeValueType::SINGLE);
        $inactiveAttribute->setInputType(AttributeInputType::SELECT);
        $inactiveAttribute->setStatus(AttributeStatus::INACTIVE);
        $entityManager->persist($inactiveAttribute);

        $entityManager->flush();

        $client->request('GET', '/api/product-attribute/attributes');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);

        // 验证数据包含激活属性，不包含非激活属性
        $attributes = $response['data'];
        $this->assertIsArray($attributes);
        $this->assertGreaterThanOrEqual(2, count($attributes));

        $codes = array_column($attributes, 'code');
        $this->assertContains('list_attr1', $codes);
        $this->assertContains('list_attr2', $codes);
        $this->assertNotContains('inactive_attr', $codes);
    }

    public function testFilterAttributesByType(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();

        $salesAttribute = $this->createTestAttribute();
        $salesAttribute->setCode('sales_filter_attr');
        $salesAttribute->setName('销售属性筛选');
        $salesAttribute->setType(AttributeType::SALES);
        $entityManager->persist($salesAttribute);

        $nonSalesAttribute = new Attribute();
        $nonSalesAttribute->setCode('non_sales_filter_attr');
        $nonSalesAttribute->setName('非销售属性筛选');
        $nonSalesAttribute->setType(AttributeType::NON_SALES);
        $nonSalesAttribute->setValueType(AttributeValueType::TEXT);
        $nonSalesAttribute->setInputType(AttributeInputType::INPUT);
        $nonSalesAttribute->setStatus(AttributeStatus::ACTIVE);
        $entityManager->persist($nonSalesAttribute);

        $entityManager->flush();

        // 筛选销售属性
        $client->request('GET', '/api/product-attribute/attributes', ['type' => AttributeType::SALES->value]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $attributes = $response['data'];
        $this->assertIsArray($attributes);

        $codes = array_column($attributes, 'code');
        $this->assertContains('sales_filter_attr', $codes);
        $this->assertNotContains('non_sales_filter_attr', $codes);
    }

    public function testSearchAttributesByName(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();

        $searchableAttribute = $this->createTestAttribute();
        $searchableAttribute->setCode('searchable_attr');
        $searchableAttribute->setName('可搜索属性');
        $entityManager->persist($searchableAttribute);

        $otherAttribute = new Attribute();
        $otherAttribute->setCode('other_attr');
        $otherAttribute->setName('其他属性');
        $otherAttribute->setType(AttributeType::NON_SALES);
        $otherAttribute->setValueType(AttributeValueType::TEXT);
        $otherAttribute->setInputType(AttributeInputType::INPUT);
        $otherAttribute->setStatus(AttributeStatus::ACTIVE);
        $entityManager->persist($otherAttribute);

        $entityManager->flush();

        // 搜索包含"搜索"的属性
        $client->request('GET', '/api/product-attribute/attributes', ['search' => '搜索']);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $attributes = $response['data'];
        $this->assertIsArray($attributes);

        $codes = array_column($attributes, 'code');
        $this->assertContains('searchable_attr', $codes);
        $this->assertNotContains('other_attr', $codes);
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        try {
            $client->request('GET', '/api/product-attribute/attributes');
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
        } catch (AccessDeniedException) {
            // 如果直接抛出AccessDeniedException，表示安全配置正确
            $this->expectNotToPerformAssertions();
        }
    }

    public function testPaginatedResponse(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        // 创建多个测试数据
        $entityManager = self::getEntityManager();

        for ($i = 1; $i <= 25; ++$i) {
            $attribute = new Attribute();
            $attribute->setCode("paginated_attr_{$i}");
            $attribute->setName("分页属性{$i}");
            $attribute->setType(AttributeType::NON_SALES);
            $attribute->setValueType(AttributeValueType::TEXT);
            $attribute->setInputType(AttributeInputType::INPUT);
            $attribute->setStatus(AttributeStatus::ACTIVE);
            $entityManager->persist($attribute);
        }

        $entityManager->flush();

        // 测试第一页
        $client->request('GET', '/api/product-attribute/attributes', ['page' => 1, 'limit' => 10]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);

        $pagination = $response['pagination'];
        $this->assertIsArray($pagination);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertGreaterThanOrEqual(25, $pagination['total']);
        $this->assertGreaterThanOrEqual(3, $pagination['last_page']);

        $data = $response['data'];
        $this->assertIsArray($data);
        $this->assertCount(10, $data);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/api/product-attribute/attributes');
    }
}
