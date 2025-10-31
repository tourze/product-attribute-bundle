<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;
use Tourze\ProductAttributeBundle\Entity\SkuAttribute;
use Tourze\ProductAttributeBundle\Entity\SpuAttribute;

/**
 * 商品属性管理菜单服务
 */
class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private readonly LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        // 创建或获取商品管理主菜单
        if (null === $item->getChild('商品管理')) {
            $item->addChild('商品管理')
                ->setLabel('商品管理')
            ;
        }

        $productMenu = $item->getChild('商品管理');
        if (null === $productMenu) {
            return;
        }

        // 创建属性管理子菜单
        if (null === $productMenu->getChild('属性管理')) {
            $productMenu->addChild('属性管理')
                ->setLabel('属性管理')
                ->setAttribute('icon', 'fas fa-tags')
            ;
        }

        $attributeMenu = $productMenu->getChild('属性管理');
        if (null === $attributeMenu) {
            return;
        }

        // 属性管理菜单
        $attributeMenu->addChild('商品属性')
            ->setLabel('商品属性')
            ->setUri($this->linkGenerator->getCurdListPage(Attribute::class))
            ->setAttribute('icon', 'fas fa-tag')
            ->setExtra('help', '管理商品的基础属性，包括销售属性、非销售属性和自定义属性')
        ;

        // 属性值管理菜单
        $attributeMenu->addChild('属性值')
            ->setLabel('属性值')
            ->setUri($this->linkGenerator->getCurdListPage(AttributeValue::class))
            ->setAttribute('icon', 'fas fa-list-ul')
            ->setExtra('help', '管理属性的可选值，支持文本、颜色和图片等形式')
        ;

        // 属性分组管理菜单
        $attributeMenu->addChild('属性分组')
            ->setLabel('属性分组')
            ->setUri($this->linkGenerator->getCurdListPage(AttributeGroup::class))
            ->setAttribute('icon', 'fas fa-layer-group')
            ->setExtra('help', '管理属性的分组，用于在前端展示时对属性进行归类')
        ;

        // 类目属性关联管理菜单
        $attributeMenu->addChild('类目属性关联')
            ->setLabel('类目属性关联')
            ->setUri($this->linkGenerator->getCurdListPage(CategoryAttribute::class))
            ->setAttribute('icon', 'fas fa-link')
            ->setExtra('help', '管理商品类目与属性的关联关系')
        ;

        // SPU属性管理菜单
        $attributeMenu->addChild('SPU属性')
            ->setLabel('SPU属性')
            ->setUri($this->linkGenerator->getCurdListPage(SpuAttribute::class))
            ->setAttribute('icon', 'fas fa-cube')
            ->setExtra('help', '管理SPU（标准产品单元）的属性值')
        ;

        // SKU属性管理菜单
        $attributeMenu->addChild('SKU属性')
            ->setLabel('SKU属性')
            ->setUri($this->linkGenerator->getCurdListPage(SkuAttribute::class))
            ->setAttribute('icon', 'fas fa-cubes')
            ->setExtra('help', '管理SKU（库存保管单元）的销售属性值')
        ;
    }
}
