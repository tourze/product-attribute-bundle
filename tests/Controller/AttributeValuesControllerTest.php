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
use Tourze\ProductAttributeBundle\Controller\AttributeValuesController;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * @internal
 */
#[CoversClass(AttributeValuesController::class)]
#[RunTestsInSeparateProcesses]
class AttributeValuesControllerTest extends AbstractWebTestCase
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

    public function testGetAttributeValues(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        // 创建测试数据
        $entityManager = self::getEntityManager();
        $attribute = $this->createTestAttribute();
        $attribute->setCode('values_attr');
        $attribute->setName('属性值属性');
        $entityManager->persist($attribute);

        $value1 = new AttributeValue();
        $value1->setCode('red');
        $value1->setValue('红色');
        $value1->setAttribute($attribute);
        $value1->setStatus(AttributeStatus::ACTIVE);
        $value1->setSortOrder(10);
        $attribute->addValue($value1); // 确保双向关系
        $entityManager->persist($value1);

        $value2 = new AttributeValue();
        $value2->setCode('blue');
        $value2->setValue('蓝色');
        $value2->setAttribute($attribute);
        $value2->setStatus(AttributeStatus::ACTIVE);
        $value2->setSortOrder(20);
        $attribute->addValue($value2); // 确保双向关系
        $entityManager->persist($value2);

        // 非激活属性值不应该在结果中
        $inactiveValue = new AttributeValue();
        $inactiveValue->setCode('inactive_color');
        $inactiveValue->setValue('非激活颜色');
        $inactiveValue->setAttribute($attribute);
        $inactiveValue->setStatus(AttributeStatus::INACTIVE);
        $attribute->addValue($inactiveValue); // 确保双向关系
        $entityManager->persist($inactiveValue);

        $entityManager->flush();

        $client->request('GET', "/api/product-attribute/attributes/{$attribute->getId()}/values");

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent, 'Response content should be a string');
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);

        $values = $response['data'];
        $this->assertIsArray($values);
        $this->assertCount(2, $values);

        // 验证排序顺序（按sortOrder降序）
        $this->assertIsArray($values[0]);
        $this->assertIsArray($values[1]);
        $this->assertEquals('blue', $values[0]['code']); // sortOrder = 20
        $this->assertEquals('red', $values[1]['code']); // sortOrder = 10

        $codes = array_column($values, 'code');
        $this->assertNotContains('inactive_color', $codes);
    }

    public function testGetAttributeValuesNotFound(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $client->request('GET', '/api/product-attribute/attributes/999999/values');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        try {
            $client->request('GET', '/api/product-attribute/attributes/1/values');
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
        $client->request($method, '/api/product-attribute/attributes/1/values');
    }

    public function testPostMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/api/product-attribute/attributes/1/values');
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/api/product-attribute/attributes/1/values');
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/api/product-attribute/attributes/1/values');
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/api/product-attribute/attributes/1/values');
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser();
        $client->loginUser($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/api/product-attribute/attributes/1/values');
    }
}
