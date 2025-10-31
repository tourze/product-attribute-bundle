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
use Tourze\ProductAttributeBundle\Controller\AttributeSearchController;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(AttributeSearchController::class)]
#[RunTestsInSeparateProcesses]
class AttributeSearchControllerTest extends AbstractWebTestCase
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

    public function testSearchWithQuery(): void
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
        $client->request('GET', '/api/product-attribute/attributes/search', ['q' => '搜索']);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

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

    public function testSearchWithEmptyQuery(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $client->request('GET', '/api/product-attribute/attributes/search', ['q' => '']);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertEquals(['data' => []], $response);
    }

    public function testSearchWithTypeFilter(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();

        $salesAttribute = $this->createTestAttribute();
        $salesAttribute->setCode('sales_search_attr');
        $salesAttribute->setName('销售搜索属性');
        $salesAttribute->setType(AttributeType::SALES);
        $entityManager->persist($salesAttribute);

        $nonSalesAttribute = new Attribute();
        $nonSalesAttribute->setCode('non_sales_search_attr');
        $nonSalesAttribute->setName('非销售搜索属性');
        $nonSalesAttribute->setType(AttributeType::NON_SALES);
        $nonSalesAttribute->setValueType(AttributeValueType::TEXT);
        $nonSalesAttribute->setInputType(AttributeInputType::INPUT);
        $nonSalesAttribute->setStatus(AttributeStatus::ACTIVE);
        $entityManager->persist($nonSalesAttribute);

        $entityManager->flush();

        // 搜索销售类型的"搜索"属性
        $client->request('GET', '/api/product-attribute/attributes/search', [
            'q' => '搜索',
            'type' => AttributeType::SALES->value,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $attributes = $response['data'];
        $this->assertIsArray($attributes);

        $codes = array_column($attributes, 'code');
        $this->assertContains('sales_search_attr', $codes);
        $this->assertNotContains('non_sales_search_attr', $codes);
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        try {
            $client->request('GET', '/api/product-attribute/attributes/search', ['q' => 'test']);
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
        } catch (AccessDeniedException) {
            // 如果直接抛出AccessDeniedException，表示安全配置正确
            $this->expectNotToPerformAssertions();
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/api/product-attribute/attributes/search');
    }

    public function testPostMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/api/product-attribute/attributes/search');
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/api/product-attribute/attributes/search');
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/api/product-attribute/attributes/search');
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/api/product-attribute/attributes/search');
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/api/product-attribute/attributes/search');
    }
}
