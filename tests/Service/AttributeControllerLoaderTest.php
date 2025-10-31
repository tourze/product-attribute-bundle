<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductAttributeBundle\Controller\AttributeListController;
use Tourze\ProductAttributeBundle\Service\AttributeControllerLoader;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(AttributeControllerLoader::class);
    }

    public function testLoad(): void
    {
        $collection = $this->service->load('resource', 'type');
        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    public function testAutoload(): void
    {
        $collection = $this->service->autoload();
        $this->assertInstanceOf(RouteCollection::class, $collection);

        // 验证路由集合不为空（应该包含Attribute相关Controller的路由）
        $routes = $collection->all();
        $this->assertNotEmpty($routes);
    }

    public function testSupports(): void
    {
        // 根据源码，supports方法总是返回false
        $result = $this->service->supports('any_resource', 'any_type');
        $this->assertFalse($result);

        $result = $this->service->supports(null, null);
        $this->assertFalse($result);

        $result = $this->service->supports(AttributeListController::class, 'annotation');
        $this->assertFalse($result);
    }

    public function testLoadCallsAutoload(): void
    {
        // load方法应该调用autoload方法
        $loadCollection = $this->service->load('test', 'test');
        $autoloadCollection = $this->service->autoload();

        // 两个方法应该返回相同的路由数量
        $this->assertEquals(count($loadCollection->all()), count($autoloadCollection->all()));
    }

    public function testIsInstanceOfInterfaces(): void
    {
        $this->assertInstanceOf(LoaderInterface::class, $this->service);
        $this->assertInstanceOf(RoutingAutoLoaderInterface::class, $this->service);
    }

    public function testConstructorInitialization(): void
    {
        $this->assertInstanceOf(AttributeControllerLoader::class, $this->service);
        $collection = $this->service->autoload();
        $this->assertInstanceOf(RouteCollection::class, $collection);
    }

    public function testRouteCollectionContainsAttributeControllerRoutes(): void
    {
        $collection = $this->service->autoload();
        $routes = $collection->all();

        // 验证路由集合包含路由
        $this->assertNotEmpty($routes);

        // 检查是否有与Attribute相关的路由
        $hasAttributeRoutes = false;
        foreach ($routes as $route) {
            $controller = $route->getDefault('_controller');
            if (is_string($controller) && str_contains($controller, 'Attribute')) {
                $hasAttributeRoutes = true;
                break;
            }
        }

        $this->assertTrue($hasAttributeRoutes, 'Route collection should contain Attribute related controller routes');
    }

    public function testMultipleCallsReturnConsistentResults(): void
    {
        $collection1 = $this->service->autoload();
        $collection2 = $this->service->autoload();

        $this->assertEquals(count($collection1->all()), count($collection2->all()));
    }

    public function testBasicOperations(): void
    {
        $this->assertInstanceOf(AttributeControllerLoader::class, $this->service);
    }
}
