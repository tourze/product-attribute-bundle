<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AttributeType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case SALES = 'sales';
    case NON_SALES = 'non_sales';
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::SALES => '销售属性',
            self::NON_SALES => '非销售属性',
            self::CUSTOM => '自定义属性',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
