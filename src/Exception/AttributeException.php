<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Exception;

class AttributeException extends \RuntimeException
{
    public static function codeAlreadyExists(string $code): self
    {
        return new self(sprintf('属性编码 "%s" 已存在', $code));
    }

    public static function valueCodeAlreadyExists(string $code): self
    {
        return new self(sprintf('属性值编码 "%s" 已存在', $code));
    }

    public static function invalidAttributeType(string $type): self
    {
        return new self(sprintf('无效的属性类型 "%s"', $type));
    }

    public static function invalidValueType(string $valueType): self
    {
        return new self(sprintf('无效的值类型 "%s"', $valueType));
    }

    public static function invalidInputType(string $inputType): self
    {
        return new self(sprintf('无效的输入类型 "%s"', $inputType));
    }

    public static function attributeValueNotBelongsToAttribute(string $attributeName): self
    {
        return new self(sprintf('属性值不属于属性 "%s"', $attributeName));
    }

    public static function onlySalesAttributeCanBeAssignedToSku(): self
    {
        return new self('只有销售属性才能分配给SKU');
    }

    public static function attributeCombinationNotUnique(): self
    {
        return new self('属性值组合不唯一，已存在相同的SKU属性组合');
    }

    /**
     * @param array<string> $attributeNames
     */
    public static function missingRequiredAttributes(array $attributeNames): self
    {
        return new self(sprintf('缺少必填属性：%s', implode('、', $attributeNames)));
    }

    public static function invalidAttributeValue(string $attributeName): self
    {
        return new self(sprintf('无效的属性值 "%s"', $attributeName));
    }
}
