<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

class AttributeValueFixtures extends Fixture implements DependentFixtureInterface
{
    public const VALUE_RED = 'attribute-value-red';
    public const VALUE_BLUE = 'attribute-value-blue';
    public const VALUE_BLACK = 'attribute-value-black';
    public const VALUE_WHITE = 'attribute-value-white';
    public const VALUE_SIZE_S = 'attribute-value-size-s';
    public const VALUE_SIZE_M = 'attribute-value-size-m';
    public const VALUE_SIZE_L = 'attribute-value-size-l';
    public const VALUE_SIZE_XL = 'attribute-value-size-xl';
    public const VALUE_COTTON = 'attribute-value-cotton';
    public const VALUE_POLYESTER = 'attribute-value-polyester';
    public const VALUE_SILK = 'attribute-value-silk';

    public function load(ObjectManager $manager): void
    {
        /** @var Attribute $colorAttribute */
        $colorAttribute = $this->getReference(AttributeFixtures::ATTR_COLOR, Attribute::class);
        /** @var Attribute $sizeAttribute */
        $sizeAttribute = $this->getReference(AttributeFixtures::ATTR_SIZE, Attribute::class);
        /** @var Attribute $materialAttribute */
        $materialAttribute = $this->getReference(AttributeFixtures::ATTR_MATERIAL, Attribute::class);

        // 颜色属性值
        $colorValues = [
            [
                'code' => 'red',
                'value' => '红色',
                'aliases' => ['红', 'Red'],
                'colorValue' => '#FF0000',
                'imageUrl' => null,
                'sortOrder' => 1,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_RED,
            ],
            [
                'code' => 'blue',
                'value' => '蓝色',
                'aliases' => ['蓝', 'Blue'],
                'colorValue' => '#0000FF',
                'imageUrl' => null,
                'sortOrder' => 2,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_BLUE,
            ],
            [
                'code' => 'black',
                'value' => '黑色',
                'aliases' => ['黑', 'Black'],
                'colorValue' => '#000000',
                'imageUrl' => null,
                'sortOrder' => 3,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_BLACK,
            ],
            [
                'code' => 'white',
                'value' => '白色',
                'aliases' => ['白', 'White'],
                'colorValue' => '#FFFFFF',
                'imageUrl' => null,
                'sortOrder' => 4,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_WHITE,
            ],
        ];

        foreach ($colorValues as $data) {
            $value = new AttributeValue();
            $value->setAttribute($colorAttribute);
            $value->setCode($data['code']);
            $value->setValue($data['value']);
            $value->setAliases($data['aliases']);
            $value->setColorValue($data['colorValue']);
            $value->setImageUrl($data['imageUrl']);
            $value->setSortOrder($data['sortOrder']);
            $value->setStatus($data['status']);

            $manager->persist($value);
            $this->addReference($data['reference'], $value);
        }

        // 尺寸属性值
        $sizeValues = [
            [
                'code' => 's',
                'value' => 'S',
                'aliases' => ['小号', 'Small'],
                'colorValue' => null,
                'imageUrl' => null,
                'sortOrder' => 1,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_SIZE_S,
            ],
            [
                'code' => 'm',
                'value' => 'M',
                'aliases' => ['中号', 'Medium'],
                'colorValue' => null,
                'imageUrl' => null,
                'sortOrder' => 2,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_SIZE_M,
            ],
            [
                'code' => 'l',
                'value' => 'L',
                'aliases' => ['大号', 'Large'],
                'colorValue' => null,
                'imageUrl' => null,
                'sortOrder' => 3,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_SIZE_L,
            ],
            [
                'code' => 'xl',
                'value' => 'XL',
                'aliases' => ['加大号', 'Extra Large'],
                'colorValue' => null,
                'imageUrl' => null,
                'sortOrder' => 4,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_SIZE_XL,
            ],
        ];

        foreach ($sizeValues as $data) {
            $value = new AttributeValue();
            $value->setAttribute($sizeAttribute);
            $value->setCode($data['code']);
            $value->setValue($data['value']);
            $value->setAliases($data['aliases']);
            $value->setColorValue($data['colorValue']);
            $value->setImageUrl($data['imageUrl']);
            $value->setSortOrder($data['sortOrder']);
            $value->setStatus($data['status']);

            $manager->persist($value);
            $this->addReference($data['reference'], $value);
        }

        // 材质属性值
        $materialValues = [
            [
                'code' => 'cotton',
                'value' => '棉质',
                'aliases' => ['纯棉', 'Cotton', '100%棉'],
                'colorValue' => null,
                'imageUrl' => null,
                'sortOrder' => 1,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_COTTON,
            ],
            [
                'code' => 'polyester',
                'value' => '聚酯纤维',
                'aliases' => ['涤纶', 'Polyester'],
                'colorValue' => null,
                'imageUrl' => null,
                'sortOrder' => 2,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_POLYESTER,
            ],
            [
                'code' => 'silk',
                'value' => '丝绸',
                'aliases' => ['真丝', 'Silk'],
                'colorValue' => null,
                'imageUrl' => null,
                'sortOrder' => 3,
                'status' => AttributeStatus::ACTIVE,
                'reference' => self::VALUE_SILK,
            ],
        ];

        foreach ($materialValues as $data) {
            $value = new AttributeValue();
            $value->setAttribute($materialAttribute);
            $value->setCode($data['code']);
            $value->setValue($data['value']);
            $value->setAliases($data['aliases']);
            $value->setColorValue($data['colorValue']);
            $value->setImageUrl($data['imageUrl']);
            $value->setSortOrder($data['sortOrder']);
            $value->setStatus($data['status']);

            $manager->persist($value);
            $this->addReference($data['reference'], $value);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AttributeFixtures::class,
        ];
    }
}
