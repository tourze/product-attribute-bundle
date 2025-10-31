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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

/**
 * 商品属性分组管理控制器
 */
#[AdminCrud(routePath: '/product-attribute/attribute-group', routeName: 'product_attribute_attribute_group')]
final class AttributeGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AttributeGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('属性分组')
            ->setEntityLabelInPlural('属性分组')
            ->setPageTitle('index', '属性分组列表')
            ->setPageTitle('new', '新建属性分组')
            ->setPageTitle('edit', '编辑属性分组')
            ->setPageTitle('detail', '属性分组详情')
            ->setHelp('index', '管理商品属性的分组，用于在前端展示时对属性进行归类')
            ->setDefaultSort(['sortOrder' => 'DESC', 'id' => 'DESC'])
            ->setSearchFields(['id', 'code', 'name'])
            ->setPaginatorPageSize(25)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 基本字段
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->onlyOnIndex()
        ;

        yield TextField::new('code', '分组编码')
            ->setHelp('唯一标识符，只能包含小写字母、数字和下划线')
            ->setRequired(true)
            ->setMaxLength(50)
        ;

        yield TextField::new('name', '分组名称')
            ->setRequired(true)
            ->setMaxLength(100)
        ;

        yield TextareaField::new('description', '分组描述')
            ->hideOnIndex()
            ->setMaxLength(500)
            ->setHelp('描述该分组的用途和包含的属性类型')
        ;

        yield BooleanField::new('isVisible', '是否显示')
            ->setHelp('前端是否显示该分组')
        ;

        yield IntegerField::new('sortOrder', '排序权重')
            ->setHelp('数值越大排序越靠前')
        ;

        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AttributeStatus::class])
            ->formatValue(function ($value) {
                return $value instanceof AttributeStatus ? $value->label() : '';
            })
        ;

        yield TextareaField::new('remark', '备注')
            ->hideOnIndex()
            ->setMaxLength(500)
        ;

        // 关联字段
        yield AssociationField::new('categoryAttributes', '关联的类目属性')
            ->onlyOnDetail()
            ->setHelp('显示使用此分组的类目属性关联记录')
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

        // 创建者字段（如果有）
        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('createdBy', '创建者')
                ->hideOnForm()
                ->formatValue(function ($value) {
                    return $value ?? '系统';
                })
            ;

            yield TextField::new('updatedBy', '更新者')
                ->hideOnForm()
                ->formatValue(function ($value) {
                    return $value ?? '系统';
                })
            ;
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 状态筛选
        $statusChoices = [];
        foreach (AttributeStatus::cases() as $case) {
            $statusChoices[$case->label()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('code', '分组编码'))
            ->add(TextFilter::new('name', '分组名称'))
            ->add(BooleanFilter::new('isVisible', '是否显示'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity')
            ->orderBy('entity.sortOrder', 'DESC')
            ->addOrderBy('entity.id', 'DESC')
        ;
    }
}
