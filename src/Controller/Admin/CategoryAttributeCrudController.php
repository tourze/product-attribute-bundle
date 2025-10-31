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
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\CategoryAttribute;

/**
 * 类目属性关联管理控制器
 */
#[AdminCrud(routePath: '/product-attribute/category-attribute', routeName: 'product_attribute_category_attribute')]
final class CategoryAttributeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategoryAttribute::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('类目属性关联')
            ->setEntityLabelInPlural('类目属性关联')
            ->setPageTitle('index', '类目属性关联列表')
            ->setPageTitle('new', '新建类目属性关联')
            ->setPageTitle('edit', '编辑类目属性关联')
            ->setPageTitle('detail', '类目属性关联详情')
            ->setHelp('index', '管理商品类目与属性的关联关系，定义不同类目下可用的属性')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 基本字段
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->onlyOnIndex()
        ;

        yield AssociationField::new('category', '所属类目')
            ->setRequired(true)
            ->formatValue(function ($value) {
                if (!is_object($value) || !method_exists($value, 'getName')) {
                    return '';
                }

                return $value->getName();
            })
            ->setHelp('选择要关联的商品类目')
        ;

        yield AssociationField::new('attribute', '关联属性')
            ->setRequired(true)
            ->formatValue(function ($value) {
                if (!$value instanceof Attribute) {
                    return '';
                }

                $name = $value->getName();
                $code = $value->getCode();

                return sprintf('%s (%s)', $name, $code);
            })
            ->setHelp('选择要关联到类目的属性')
        ;

        yield AssociationField::new('group', '属性分组')
            ->formatValue(function ($value) {
                if (!is_object($value) || !method_exists($value, 'getName')) {
                    return '无分组';
                }

                return $value->getName();
            })
            ->setHelp('可选：将属性归类到特定分组中')
        ;

        // 配置字段
        yield BooleanField::new('isRequired', '是否必填')
            ->setHelp('在此类目下该属性是否为必填项')
        ;

        yield BooleanField::new('isVisible', '是否显示')
            ->setHelp('在前端商品编辑页面是否显示该属性')
        ;

        yield IntegerField::new('sortOrder', '排序权重')
            ->setHelp('在类目下属性的显示顺序，数值越大排序越靠前')
        ;

        yield CodeEditorField::new('config', '其他配置')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->setHelp('JSON格式的扩展配置信息')
        ;

        yield BooleanField::new('isInherited', '是否继承')
            ->hideOnIndex()
            ->setHelp('是否从父类目继承此属性关联')
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
            ->add(EntityFilter::new('category', '所属类目'))
            ->add(EntityFilter::new('attribute', '关联属性'))
            ->add(EntityFilter::new('group', '属性分组'))
            ->add(BooleanFilter::new('isRequired', '是否必填'))
            ->add(BooleanFilter::new('isVisible', '是否显示'))
            ->add(BooleanFilter::new('isInherited', '是否继承'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity')
            ->leftJoin('entity.category', 'category')
            ->leftJoin('entity.attribute', 'attribute')
            ->leftJoin('entity.group', 'attributeGroup')
            ->addSelect('category', 'attribute', 'attributeGroup')
            ->orderBy('category.name', 'ASC')
            ->addOrderBy('entity.sortOrder', 'DESC')
            ->addOrderBy('attribute.name', 'ASC')
        ;
    }
}
