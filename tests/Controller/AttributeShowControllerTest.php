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
use Tourze\ProductAttributeBundle\Controller\AttributeShowController;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(AttributeShowController::class)]
#[RunTestsInSeparateProcesses]
class AttributeShowControllerTest extends AbstractWebTestCase
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

    public function testGetAttributeById(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $attribute->setCode('detail_attr');
        $attribute->setName('详情属性');
        $attribute->setDescription('详情属性描述');
        $entityManager->persist($attribute);
        $entityManager->flush();

        $client->request('GET', "/api/product-attribute/attributes/{$attribute->getId()}");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);

        $data = $response['data'];
        $this->assertIsArray($data);
        $this->assertEquals($attribute->getId(), $data['id']);
        $this->assertEquals('detail_attr', $data['code']);
        $this->assertEquals('详情属性', $data['name']);
        $this->assertEquals(AttributeType::SALES->value, $data['type']);
    }

    public function testGetAttributeByIdNotFound(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $client->request('GET', '/api/product-attribute/attributes/999999');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        try {
            $client->request('GET', '/api/product-attribute/attributes/1');
            $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
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
        $client->request($method, '/api/product-attribute/attributes/1');
    }

    public function testPostMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/api/product-attribute/attributes/1');
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/api/product-attribute/attributes/1');
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/api/product-attribute/attributes/1');
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/api/product-attribute/attributes/1');
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/api/product-attribute/attributes/1');
    }
}
