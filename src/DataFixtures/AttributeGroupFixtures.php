<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

class AttributeGroupFixtures extends Fixture
{
    public const GROUP_BASIC = 'attribute-group-basic';
    public const GROUP_APPEARANCE = 'attribute-group-appearance';
    public const GROUP_TECHNICAL = 'attribute-group-technical';

    public function load(ObjectManager $manager): void
    {
        $groups = [
            [
                'code' => 'basic',
                'name' => '基本信息',
                'icon' => 'fa-info-circle',
                'isExpanded' => true,
                'sortOrder' => 1,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::GROUP_BASIC,
            ],
            [
                'code' => 'appearance',
                'name' => '外观属性',
                'icon' => 'fa-palette',
                'isExpanded' => true,
                'sortOrder' => 2,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::GROUP_APPEARANCE,
            ],
            [
                'code' => 'technical',
                'name' => '技术参数',
                'icon' => 'fa-cog',
                'isExpanded' => false,
                'sortOrder' => 3,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::GROUP_TECHNICAL,
            ],
        ];

        foreach ($groups as $data) {
            $group = new AttributeGroup();
            $group->setCode($data['code']);
            $group->setName($data['name']);
            $group->setIcon($data['icon']);
            $group->setIsExpanded($data['isExpanded']);
            $group->setSortOrder($data['sortOrder']);
            $group->setStatus($data['status']);

            $manager->persist($group);
            $this->addReference($data['reference'], $group);
        }

        $manager->flush();
    }
}
