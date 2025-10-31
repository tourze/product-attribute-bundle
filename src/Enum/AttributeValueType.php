<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AttributeValueType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case SINGLE = 'single';
    case MULTIPLE = 'multiple';
    case TEXT = 'text';
    case NUMBER = 'number';
    case DATE = 'date';
    case BOOLEAN = 'boolean';

    public function getLabel(): string
    {
        return match ($this) {
            self::SINGLE => '单选',
            self::MULTIPLE => '多选',
            self::TEXT => '文本',
            self::NUMBER => '数字',
            self::DATE => '日期',
            self::BOOLEAN => '布尔值',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function isEnum(): bool
    {
        return self::SINGLE === $this || self::MULTIPLE === $this;
    }
}
