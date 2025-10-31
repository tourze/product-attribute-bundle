<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;

#[ORM\Entity(repositoryClass: AttributeRepository::class)]
#[ORM\Table(name: 'product_attribute', options: ['comment' => '商品属性表'])]
#[ORM\UniqueConstraint(name: 'uk_attribute_code', columns: ['code'])]
#[UniqueEntity(fields: ['code'], message: '属性编码已经存在，请使用不同的编码')]
class Attribute implements \Stringable
{
    use SnowflakeKeyAware;
    use BlameableAware;
    use TimestampableAware;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, options: ['comment' => '属性编码'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50, minMessage: '属性编码至少需要2个字符')]
    #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]*$/', message: '属性编码必须以小写字母开头，只能包含小写字母、数字和下划线')]
    private string $code = '';

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '属性名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, enumType: AttributeType::class, options: ['comment' => '属性类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AttributeType::class, 'cases'])]
    #[IndexColumn]
    private AttributeType $type = AttributeType::NON_SALES;

    #[ORM\Column(type: Types::STRING, enumType: AttributeValueType::class, options: ['comment' => '值类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AttributeValueType::class, 'cases'])]
    private AttributeValueType $valueType = AttributeValueType::TEXT;

    #[ORM\Column(type: Types::STRING, enumType: AttributeInputType::class, options: ['comment' => '输入类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AttributeInputType::class, 'cases'])]
    private AttributeInputType $inputType = AttributeInputType::INPUT;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => '单位'])]
    #[Assert\Length(max: 20)]
    private ?string $unit = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否必填'])]
    #[Assert\Type(type: 'bool')]
    private bool $isRequired = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否可搜索'])]
    #[Assert\Type(type: 'bool')]
    private bool $isSearchable = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否可筛选'])]
    #[Assert\Type(type: 'bool')]
    private bool $isFilterable = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否支持多选'])]
    #[Assert\Type(type: 'bool')]
    private bool $isMultiple = false;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '排序权重'])]
    #[Assert\Type(type: 'int')]
    private int $sortOrder = 0;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '配置信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $config = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证规则'])]
    #[Assert\Type(type: 'array')]
    private ?array $validationRules = null;

    #[ORM\Column(type: Types::STRING, enumType: AttributeStatus::class, options: ['default' => 'active', 'comment' => '状态'])]
    #[Assert\Choice(callback: [AttributeStatus::class, 'cases'])]
    #[IndexColumn]
    private AttributeStatus $status = AttributeStatus::ACTIVE;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '属性描述'])]
    #[Assert\Length(max: 10000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 500)]
    private ?string $remark = null;

    /**
     * @var Collection<int, AttributeValue>
     */
    #[ORM\OneToMany(mappedBy: 'attribute', targetEntity: AttributeValue::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(value: ['sortOrder' => 'ASC'])]
    private Collection $values;

    /**
     * @var Collection<int, CategoryAttribute>
     */
    #[ORM\OneToMany(mappedBy: 'attribute', targetEntity: CategoryAttribute::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $categoryAttributes;

    public function __construct()
    {
        $this->values = new ArrayCollection();
        $this->categoryAttributes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return '' !== $this->name ? $this->name : ('' !== $this->code ? $this->code : '');
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name ?? '';
    }

    public function getType(): AttributeType
    {
        return $this->type;
    }

    public function setType(AttributeType $type): void
    {
        $this->type = $type;
    }

    public function getValueType(): AttributeValueType
    {
        return $this->valueType;
    }

    public function setValueType(AttributeValueType $valueType): void
    {
        $this->valueType = $valueType;
    }

    public function getInputType(): AttributeInputType
    {
        return $this->inputType;
    }

    public function setInputType(AttributeInputType $inputType): void
    {
        $this->inputType = $inputType;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    public function setIsSearchable(bool $isSearchable): void
    {
        $this->isSearchable = $isSearchable;
    }

    public function isFilterable(): bool
    {
        return $this->isFilterable;
    }

    public function setIsFilterable(bool $isFilterable): void
    {
        $this->isFilterable = $isFilterable;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function setIsMultiple(bool $isMultiple): void
    {
        $this->isMultiple = $isMultiple;
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

    /**
     * @return array<string, mixed>|null
     */
    public function getValidationRules(): ?array
    {
        return $this->validationRules;
    }

    /**
     * @param array<string, mixed>|null $validationRules
     */
    public function setValidationRules(?array $validationRules): void
    {
        $this->validationRules = $validationRules;
    }

    public function getStatus(): AttributeStatus
    {
        return $this->status;
    }

    public function setStatus(AttributeStatus $status): void
    {
        $this->status = $status;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    /**
     * @return Collection<int, AttributeValue>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    public function addValue(AttributeValue $value): void
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            $value->setAttribute($this);
        }
    }

    public function removeValue(AttributeValue $value): void
    {
        if ($this->values->removeElement($value)) {
            if ($value->getAttribute() === $this) {
                $value->setAttribute(null);
            }
        }
    }

    /**
     * @return Collection<int, CategoryAttribute>
     */
    public function getCategoryAttributes(): Collection
    {
        return $this->categoryAttributes;
    }

    public function isSalesAttribute(): bool
    {
        return AttributeType::SALES === $this->type;
    }

    public function isNonSalesAttribute(): bool
    {
        return AttributeType::NON_SALES === $this->type;
    }

    public function isCustomAttribute(): bool
    {
        return AttributeType::CUSTOM === $this->type;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isEnumType(): bool
    {
        return $this->valueType->isEnum();
    }
}
