<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Repository\AttributeGroupRepository;

#[ORM\Entity(repositoryClass: AttributeGroupRepository::class)]
#[ORM\Table(name: 'product_attribute_group', options: ['comment' => '商品属性分组表'])]
#[ORM\UniqueConstraint(name: 'uk_group_code', columns: ['code'])]
class AttributeGroup implements \Stringable
{
    use SnowflakeKeyAware;
    use BlameableAware;
    use TimestampableAware;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, options: ['comment' => '分组编码'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]*$/', message: '分组编码必须以小写字母开头，只能包含小写字母、数字和下划线')]
    private string $code = '';

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '分组名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '分组描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '分组图标'])]
    #[Assert\Length(max: 50)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否默认展开'])]
    #[Assert\Type(type: 'bool')]
    private bool $isExpanded = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否显示'])]
    #[Assert\Type(type: 'bool')]
    private bool $isVisible = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '排序权重'])]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::STRING, enumType: AttributeStatus::class, options: ['default' => 'active', 'comment' => '状态'])]
    #[Assert\Choice(callback: [AttributeStatus::class, 'cases'])]
    #[IndexColumn]
    private AttributeStatus $status = AttributeStatus::ACTIVE;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 500)]
    private ?string $remark = null;

    /**
     * @var Collection<int, CategoryAttribute>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: CategoryAttribute::class)]
    private Collection $categoryAttributes;

    public function __construct()
    {
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function isExpanded(): bool
    {
        return $this->isExpanded;
    }

    public function setIsExpanded(bool $isExpanded): void
    {
        $this->isExpanded = $isExpanded;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): void
    {
        $this->isVisible = $isVisible;
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

    /**
     * @return Collection<int, CategoryAttribute>
     */
    public function getCategoryAttributes(): Collection
    {
        return $this->categoryAttributes;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }
}
