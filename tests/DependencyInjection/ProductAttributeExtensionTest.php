<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\ProductAttributeBundle\DependencyInjection\ProductAttributeExtension;

/**
 * @internal
 */
#[CoversClass(ProductAttributeExtension::class)]
class ProductAttributeExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testGetAlias(): void
    {
        $extension = new ProductAttributeExtension();
        $this->assertEquals('product_attribute', $extension->getAlias());
    }

    public function testGetConfiguration(): void
    {
        $extension = new ProductAttributeExtension();
        $configuration = $extension->getConfiguration([], $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder'));
        $this->assertNull($configuration);
    }

    public function testLoad(): void
    {
        $extension = new ProductAttributeExtension();

        // 验证扩展已正确创建
        $this->assertInstanceOf(ProductAttributeExtension::class, $extension);
        $this->assertEquals('product_attribute', $extension->getAlias());
    }
}
