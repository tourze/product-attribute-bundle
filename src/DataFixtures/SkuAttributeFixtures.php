<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\ProductAttributeBundle\Entity\SkuAttribute;
use Tourze\ProductCoreBundle\DataFixtures\BasicSpuFixtures;
use Tourze\ProductCoreBundle\Entity\Sku;

class SkuAttributeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 创建一些示例SKU属性
        $skuAttributes = [
            ['name' => '颜色', 'value' => '红色'],
            ['name' => '尺寸', 'value' => 'L'],
            ['name' => '材质', 'value' => '棉'],
        ];

        foreach ($skuAttributes as $index => $data) {
            // 只有在有有效的SKU引用时才创建SkuAttribute
            if ($this->hasReference(BasicSpuFixtures::BASIC_SKU_REFERENCE, Sku::class)) {
                $skuAttribute = new SkuAttribute();
                $skuAttribute->setName($data['name']);
                $skuAttribute->setValue($data['value']);
                $skuAttribute->setSku($this->getReference(BasicSpuFixtures::BASIC_SKU_REFERENCE, Sku::class));

                $manager->persist($skuAttribute);
                $this->addReference('sku-attribute-' . ($index + 1), $skuAttribute);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BasicSpuFixtures::class,
        ];
    }
}
