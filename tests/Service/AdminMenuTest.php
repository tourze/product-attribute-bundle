<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\ProductAttributeBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function getMenuProviderClass(): string
    {
        return AdminMenu::class;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function getExpectedMenuItems(): array
    {
        return [
            '商品管理' => [
                '属性管理',
                '属性组管理',
                '属性值管理',
                '类目属性关联',
                'SPU属性管理',
                'SKU属性管理',
            ],
        ];
    }
}
