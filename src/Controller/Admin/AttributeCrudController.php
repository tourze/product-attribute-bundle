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
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;

/**
 * 商品属性管理控制器
 */
#[AdminCrud(routePath: '/product-attribute/attribute', routeName: 'product_attribute_attribute')]
final class AttributeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Attribute::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('商品属性')
            ->setEntityLabelInPlural('商品属性')
            ->setPageTitle('index', '商品属性列表')
            ->setPageTitle('new', '新建商品属性')
            ->setPageTitle('edit', '编辑商品属性')
            ->setPageTitle('detail', '商品属性详情')
            ->setHelp('index', '管理商品属性，包括销售属性、非销售属性和自定义属性')
            ->setDefaultSort(['id' => 'DESC'])
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

        yield TextField::new('code', '属性编码')
            ->setHelp('唯一标识符，只能包含小写字母、数字和下划线')
            ->setRequired(true)
            ->setMaxLength(50)
        ;

        yield TextField::new('name', '属性名称')
            ->setRequired(true)
            ->setMaxLength(100)
        ;

        yield ChoiceField::new('type', '属性类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AttributeType::class])
            ->formatValue(function ($value) {
                return $value instanceof AttributeType ? $value->label() : '';
            })
            ->setHelp('销售属性用于区分不同SKU，非销售属性仅用于商品描述')
        ;

        yield ChoiceField::new('valueType', '值类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AttributeValueType::class])
            ->formatValue(function ($value) {
                return $value instanceof AttributeValueType ? $value->label() : '';
            })
        ;

        yield ChoiceField::new('inputType', '输入类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AttributeInputType::class])
            ->formatValue(function ($value) {
                return $value instanceof AttributeInputType ? $value->label() : '';
            })
        ;

        yield TextField::new('unit', '单位')
            ->hideOnIndex()
            ->setMaxLength(20)
        ;

        // 布尔字段
        yield BooleanField::new('isRequired', '是否必填')
            ->hideOnIndex()
        ;

        yield BooleanField::new('isMultiple', '是否多选')
            ->hideOnIndex()
            ->setHelp('允许选择多个属性值')
        ;

        yield BooleanField::new('isSearchable', '是否可搜索')
            ->hideOnIndex()
        ;

        yield BooleanField::new('isFilterable', '是否可筛选')
            ->hideOnIndex()
        ;

        yield IntegerField::new('sortOrder', '排序权重')
            ->hideOnIndex()
            ->setHelp('数值越大排序越靠前')
        ;

        // 高级配置字段
        yield CodeEditorField::new('config', '配置信息')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->setHelp('JSON格式的额外配置信息')
        ;

        yield CodeEditorField::new('validationRules', '验证规则')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->setHelp('JSON格式的验证规则配置')
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
        yield AssociationField::new('values', '属性值列表')
            ->onlyOnDetail()
            ->setTemplatePath('@EasyAdmin/crud/field/association.html.twig')
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
        // 属性类型筛选
        $typeChoices = [];
        foreach (AttributeType::cases() as $case) {
            $typeChoices[$case->label()] = $case->value;
        }

        // 值类型筛选
        $valueTypeChoices = [];
        foreach (AttributeValueType::cases() as $case) {
            $valueTypeChoices[$case->label()] = $case->value;
        }

        // 状态筛选
        $statusChoices = [];
        foreach (AttributeStatus::cases() as $case) {
            $statusChoices[$case->label()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('code', '属性编码'))
            ->add(TextFilter::new('name', '属性名称'))
            ->add(ChoiceFilter::new('type', '属性类型')->setChoices($typeChoices))
            ->add(ChoiceFilter::new('valueType', '值类型')->setChoices($valueTypeChoices))
            ->add(BooleanFilter::new('isRequired', '是否必填'))
            ->add(BooleanFilter::new('isSearchable', '是否可搜索'))
            ->add(BooleanFilter::new('isFilterable', '是否可筛选'))
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
