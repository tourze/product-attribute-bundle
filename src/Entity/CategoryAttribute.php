<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\ProductAttributeBundle\Repository\CategoryAttributeRepository;

#[ORM\Entity(repositoryClass: CategoryAttributeRepository::class)]
#[ORM\Table(name: 'product_category_attribute', options: ['comment' => '类目属性关联表'])]
#[ORM\UniqueConstraint(name: 'uk_category_attribute', columns: ['category_id', 'attribute_id'])]
class CategoryAttribute implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: Catalog::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Catalog $category = null;

    #[ORM\ManyToOne(targetEntity: Attribute::class, inversedBy: 'categoryAttributes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Attribute $attribute = null;

    #[ORM\ManyToOne(targetEntity: AttributeGroup::class, inversedBy: 'categoryAttributes')]
    #[ORM\JoinColumn(name: 'attribute_group_id', nullable: true)]
    private ?AttributeGroup $group = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否必填(覆盖默认)'])]
    #[Assert\Type(type: 'bool')]
    private ?bool $isRequired = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否显示'])]
    #[Assert\Type(type: 'bool')]
    private bool $isVisible = true;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '默认值'])]
    #[Assert\Length(max: 500)]
    private ?string $defaultValue = null;

    /**
     * @var array<string|int|float|bool>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '允许的值范围'])]
    #[Assert\Type(type: 'array')]
    private ?array $allowedValues = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '排序权重'])]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $sortOrder = 0;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '其他配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $config = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否继承自父类目'])]
    #[Assert\Type(type: 'bool')]
    private bool $isInherited = false;

    public function __toString(): string
    {
        $categoryName = $this->category?->getName() ?? '';
        $attributeName = $this->attribute?->getName() ?? '';

        return ('' !== $categoryName && '' !== $attributeName) ? "{$categoryName} - {$attributeName}" : ('' !== $categoryName ? $categoryName : $attributeName);
    }

    public function getCategory(): ?Catalog
    {
        return $this->category;
    }

    public function setCategory(?Catalog $category): void
    {
        $this->category = $category;
    }

    public function getCategoryId(): ?string
    {
        return $this->category?->getId();
    }

    public function setCategoryId(?string $categoryId): void
    {
        // 这个方法主要用于测试，实际应该通过 setCategory 设置
    }

    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    public function setAttribute(?Attribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getGroup(): ?AttributeGroup
    {
        return $this->group;
    }

    public function setGroup(?AttributeGroup $group): void
    {
        $this->group = $group;
    }

    public function getIsRequired(): ?bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(?bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): void
    {
        $this->isVisible = $isVisible;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return array<string|int|float|bool>|null
     */
    public function getAllowedValues(): ?array
    {
        return $this->allowedValues;
    }

    /**
     * @param array<string|int|float|bool>|null $allowedValues
     */
    public function setAllowedValues(?array $allowedValues): void
    {
        $this->allowedValues = $allowedValues;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    public function isInherited(): bool
    {
        return $this->isInherited;
    }

    public function setIsInherited(bool $isInherited): void
    {
        $this->isInherited = $isInherited;
    }

    /**
     * 获取是否必填（本地设置）
     */
    public function isRequired(): ?bool
    {
        return $this->isRequired;
    }

    /**
     * 获取实际是否必填（考虑覆盖）
     */
    public function isEffectiveRequired(): bool
    {
        if (null !== $this->isRequired) {
            return $this->isRequired;
        }

        return $this->attribute?->isRequired() ?? false;
    }

    /**
     * 检查值是否在允许范围内
     * @param array<mixed> $value
     */
    public function isValueAllowed(string|int|float|bool|array $value): bool
    {
        if (null === $this->allowedValues || 0 === count($this->allowedValues)) {
            return true;
        }

        if (is_array($value)) {
            return 0 === count(array_diff($value, $this->allowedValues));
        }

        return in_array($value, $this->allowedValues, true);
    }
}
