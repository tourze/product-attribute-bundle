<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\SpuAttribute;

/**
 * SPU属性管理控制器
 */
#[AdminCrud(routePath: '/product-attribute/spu-attribute', routeName: 'product_attribute_spu_attribute')]
final class SpuAttributeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SpuAttribute::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SPU属性')
            ->setEntityLabelInPlural('SPU属性')
            ->setPageTitle('index', 'SPU属性列表')
            ->setPageTitle('new', '新建SPU属性')
            ->setPageTitle('edit', '编辑SPU属性')
            ->setPageTitle('detail', 'SPU属性详情')
            ->setHelp('index', '管理SPU（标准产品单元）的属性值，用于描述商品的基本特征')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'name', 'value'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->getBasicFields();
        yield from $this->getValueFields();

        if (Crud::PAGE_DETAIL === $pageName) {
            yield from $this->getDetailFields();
        }

        yield from $this->getTimestampFields();
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getBasicFields(): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->onlyOnIndex()
        ;

        yield AssociationField::new('spu', '所属SPU')
            ->setRequired(true)
            ->formatValue(fn ($value) => $this->formatSpuValue($value))
            ->setHelp('选择要关联的SPU商品')
        ;

        yield AssociationField::new('attribute', '属性')
            ->setRequired(true)
            ->formatValue(fn ($value) => $this->formatAttributeValue($value))
            ->setHelp('选择要设置的属性')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getValueFields(): iterable
    {
        yield TextField::new('name', '属性名')
            ->setRequired(true)
            ->setMaxLength(30)
            ->setHelp('属性的名称')
        ;

        yield TextField::new('value', '属性值')
            ->setRequired(true)
            ->setMaxLength(64)
            ->setHelp('属性的具体值')
        ;

        yield TextField::new('remark', '备注')
            ->setMaxLength(100)
            ->hideOnIndex()
            ->setHelp('可选的备注信息')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getDetailFields(): iterable
    {
        yield TextField::new('displayValue', '显示值')
            ->onlyOnDetail()
            ->formatValue(function ($value, $entity) {
                if (!$entity instanceof SpuAttribute) {
                    return '';
                }

                return sprintf('%s: %s', $entity->getName(), $entity->getValue());
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getTimestampFields(): iterable
    {
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    private function formatSpuValue(mixed $value): string
    {
        if (!is_object($value) || !method_exists($value, 'getTitle') || !method_exists($value, 'getId')) {
            return '';
        }

        $title = $value->getTitle();
        $id = $value->getId();
        $titleStr = is_scalar($title) ? (string) $title : '未命名';
        $idStr = is_scalar($id) ? (string) $id : 'N/A';

        return sprintf('%s (ID: %s)', $titleStr, $idStr);
    }

    private function formatAttributeValue(mixed $value): string
    {
        if (!$value instanceof Attribute) {
            return '';
        }

        return sprintf('%s (%s)', $value->getName(), $value->getCode());
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('spu', '所属SPU'))
            ->add(EntityFilter::new('attribute', '属性'))
            ->add(TextFilter::new('name', '属性名'))
            ->add(TextFilter::new('value', '属性值'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity')
            ->leftJoin('entity.spu', 'spu')
            ->leftJoin('entity.attribute', 'attribute')
            ->addSelect('spu', 'attribute')
            ->orderBy('spu.title', 'ASC')
            ->addOrderBy('attribute.sortOrder', 'DESC')
            ->addOrderBy('entity.id', 'DESC')
        ;
    }
}
