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
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Entity\SkuAttribute;

/**
 * SKU属性管理控制器
 */
#[AdminCrud(routePath: '/product-attribute/sku-attribute', routeName: 'product_attribute_sku_attribute')]
final class SkuAttributeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SkuAttribute::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SKU属性')
            ->setEntityLabelInPlural('SKU属性')
            ->setPageTitle('index', 'SKU属性列表')
            ->setPageTitle('new', '新建SKU属性')
            ->setPageTitle('edit', '编辑SKU属性')
            ->setPageTitle('detail', 'SKU属性详情')
            ->setHelp('index', '管理SKU（库存保管单元）的销售属性值，用于区分不同规格的商品')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id'])
            ->setPaginatorPageSize(50)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 基本字段
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->onlyOnIndex()
        ;
        yield TextField::new('skuId', 'SKU ID')
            ->setRequired(true)
            ->setHelp('输入SKU商品的ID')
        ;

        yield AssociationField::new('attribute', '销售属性')
            ->setRequired(true)
            ->formatValue(function ($value) {
                if (!$value instanceof Attribute) {
                    return '';
                }

                $name = $value->getName();
                $code = $value->getCode();

                return sprintf('%s (%s)', $name, $code);
            })
            ->setHelp('选择销售属性，仅限类型为"销售属性"的属性')
        ;

        yield TextField::new('name', '属性名称')
            ->setRequired(true)
            ->setHelp('属性名称，如"颜色"、"尺寸"等')
        ;

        yield TextField::new('value', '属性值')
            ->setRequired(true)
            ->setHelp('属性的具体值，如"红色"、"XL"等')
        ;

        yield AssociationField::new('attributeValue', '属性值')
            ->setRequired(true)
            ->formatValue(function ($value) {
                if (!$value instanceof AttributeValue) {
                    return '';
                }

                $displayValue = $value->getValue();
                $display = $displayValue;
                $colorValue = $value->getColorValue();
                if (null !== $colorValue) {
                    $display .= sprintf(' <span style="display:inline-block;width:16px;height:16px;background-color:%s;border:1px solid #ccc;vertical-align:middle;margin-left:5px;"></span>', $colorValue);
                }

                return $display;
            })
            ->setHelp('选择该销售属性的具体值')
        ;

        // 组合显示字段
        yield AssociationField::new('attribute', '属性组合')
            ->onlyOnDetail()
            ->formatValue(function ($value, $entity) {
                if (!$entity instanceof SkuAttribute) {
                    return '';
                }

                $attribute = $entity->getAttribute();
                $attributeName = '未知属性';
                if ($attribute instanceof Attribute) {
                    $attributeName = $attribute->getName();
                }

                $attributeValue = $entity->getAttributeValue();
                $valueText = '';
                if ($attributeValue instanceof AttributeValue) {
                    $valueText = $attributeValue->getValue();
                } else {
                    $valueText = $entity->getValue();
                }

                return sprintf('%s: %s', $attributeName, $valueText);
            })
            ->setHelp('显示属性和值的完整组合')
        ;

        // 时间戳字段
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
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
            ->add(EntityFilter::new('sku', '所属SKU'))
            ->add(EntityFilter::new('attribute', '销售属性'))
            ->add(EntityFilter::new('attributeValue', '属性值'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity')
            ->leftJoin('entity.sku', 'sku')
            ->leftJoin('entity.attribute', 'attribute')
            ->leftJoin('entity.attributeValue', 'attributeValue')
            ->addSelect('sku', 'attribute', 'attributeValue')
            ->orderBy('sku.id', 'ASC')
            ->addOrderBy('attribute.sortOrder', 'DESC')
            ->addOrderBy('attributeValue.sortOrder', 'DESC')
            ->addOrderBy('entity.id', 'DESC')
        ;
    }
}
