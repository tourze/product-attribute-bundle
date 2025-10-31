<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AttributeInputType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';
    case RADIO = 'radio';
    case INPUT = 'input';
    case TEXTAREA = 'textarea';
    case DATEPICKER = 'datepicker';
    case NUMBER = 'number';
    case SWITCH = 'switch';

    public function getLabel(): string
    {
        return match ($this) {
            self::SELECT => '下拉框',
            self::CHECKBOX => '复选框',
            self::RADIO => '单选框',
            self::INPUT => '输入框',
            self::TEXTAREA => '文本域',
            self::DATEPICKER => '日期选择器',
            self::NUMBER => '数字输入框',
            self::SWITCH => '开关',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
