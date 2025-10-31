<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\ProductAttributeBundle\Entity\SpuAttribute;
use Tourze\ProductCoreBundle\DataFixtures\BasicSpuFixtures;
use Tourze\ProductCoreBundle\Entity\Spu;

class SpuAttributeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 创建一些示例SPU属性
        $spuAttributes = [
            ['name' => '品牌', 'value' => 'Nike'],
            ['name' => '产地', 'value' => '中国'],
            ['name' => '适用场景', 'value' => '运动'],
        ];

        // 只有当SPU存在时才创建属性
        if (!$this->hasReference(BasicSpuFixtures::BASIC_SPU_REFERENCE, Spu::class)) {
            return;
        }

        $spu = $this->getReference(BasicSpuFixtures::BASIC_SPU_REFERENCE, Spu::class);

        foreach ($spuAttributes as $index => $data) {
            $spuAttribute = new SpuAttribute();
            $spuAttribute->setName($data['name']);
            $spuAttribute->setValue($data['value']);
            $spuAttribute->setSpu($spu);

            $manager->persist($spuAttribute);
            $this->addReference('spu-attribute-' . ($index + 1), $spuAttribute);
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
