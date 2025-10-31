<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\ProductAttributeBundle\ProductAttributeBundle;

/**
 * @internal
 */
#[CoversClass(ProductAttributeBundle::class)]
#[RunTestsInSeparateProcesses]
class ProductAttributeBundleTest extends AbstractBundleTestCase
{
}
