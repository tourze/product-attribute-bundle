<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Repository\AttributeValueRepository;

#[ORM\Entity(repositoryClass: AttributeValueRepository::class)]
#[ORM\Table(name: 'product_attribute_value', options: ['comment' => '商品属性值表'])]
#[ORM\UniqueConstraint(name: 'uk_attribute_value_code', columns: ['attribute_id', 'code'])]
class AttributeValue implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: Attribute::class, inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Attribute $attribute = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '属性值编码'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $code = '';

    #[ORM\Column(type: Types::STRING, length: 200, options: ['comment' => '属性值'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private string $value = '';

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '别名列表'])]
    #[Assert\Type(type: 'array')]
    #[Assert\All(constraints: [
        new Assert\Type(type: 'string'),
        new Assert\Length(max: 100),
    ])]
    private ?array $aliases = null;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true, options: ['comment' => '颜色值(HEX)'])]
    #[Assert\Length(max: 7)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: '颜色值必须是有效的HEX格式')]
    private ?string $colorValue = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '图片URL'])]
    #[Assert\Length(max: 500)]
    #[Assert\Url]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '排序权重'])]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::STRING, enumType: AttributeStatus::class, options: ['default' => 'active', 'comment' => '状态'])]
    #[Assert\Choice(callback: [AttributeStatus::class, 'cases'])]
    private AttributeStatus $status = AttributeStatus::ACTIVE;

    public function __toString(): string
    {
        return '' !== $this->value ? $this->value : ('' !== $this->code ? $this->code : '');
    }

    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    public function setAttribute(?Attribute $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code ?? '';
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value ?? '';
    }

    /**
     * @return array<string>|null
     */
    public function getAliases(): ?array
    {
        return $this->aliases;
    }

    /**
     * @param array<string>|null $aliases
     */
    public function setAliases(?array $aliases): void
    {
        $this->aliases = $aliases;
    }

    public function addAlias(string $alias): void
    {
        $aliases = $this->aliases ?? [];
        if (!in_array($alias, $aliases, true)) {
            $aliases[] = $alias;
            $this->aliases = $aliases;
        }
    }

    public function removeAlias(string $alias): void
    {
        if (null !== $this->aliases) {
            $this->aliases = array_values(array_diff($this->aliases, [$alias]));
            if (0 === count($this->aliases)) {
                $this->aliases = null;
            }
        }
    }

    public function getColorValue(): ?string
    {
        return $this->colorValue;
    }

    public function setColorValue(?string $colorValue): void
    {
        $this->colorValue = $colorValue;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getStatus(): AttributeStatus
    {
        return $this->status;
    }

    public function setStatus(AttributeStatus $status): void
    {
        $this->status = $status;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * 获取标签，优先返回value值，如果为空则返回code
     */
    public function getLabel(): string
    {
        return '' !== $this->value ? $this->value : $this->code;
    }
}
