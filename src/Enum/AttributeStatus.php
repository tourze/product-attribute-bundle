<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AttributeStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => '启用',
            self::INACTIVE => '禁用',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }
}
