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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

/**
 * 商品属性值管理控制器
 */
#[AdminCrud(routePath: '/product-attribute/attribute-value', routeName: 'product_attribute_attribute_value')]
final class AttributeValueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AttributeValue::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('属性值')
            ->setEntityLabelInPlural('属性值')
            ->setPageTitle('index', '属性值列表')
            ->setPageTitle('new', '新建属性值')
            ->setPageTitle('edit', '编辑属性值')
            ->setPageTitle('detail', '属性值详情')
            ->setHelp('index', '管理商品属性的可选值，支持文本、颜色和图片等形式')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'code', 'value'])
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

        yield AssociationField::new('attribute', '所属属性')
            ->setRequired(true)
            ->formatValue(function ($value, $entity) {
                if (!$value instanceof Attribute) {
                    return '';
                }

                $name = $value->getName();
                $code = $value->getCode();

                return sprintf('%s (%s)', $name, $code);
            })
        ;

        yield TextField::new('code', '属性值编码')
            ->setHelp('在同一属性下唯一的标识符')
            ->setRequired(true)
            ->setMaxLength(50)
        ;

        yield TextField::new('value', '属性值')
            ->setRequired(true)
            ->setMaxLength(200)
            ->setHelp('显示给用户的值内容')
        ;

        // 扩展字段
        yield TextField::new('aliases', '别名列表')
            ->hideOnIndex()
            ->setHelp('JSON格式的别名数组，用于搜索匹配')
            ->formatValue(function ($value) {
                if (is_array($value)) {
                    return implode(', ', $value);
                }

                return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
            })
        ;

        yield ColorField::new('colorValue', '颜色值')
            ->hideOnIndex()
            ->setHelp('HEX格式的颜色代码，如 #FF0000')
        ;

        yield UrlField::new('imageUrl', '图片链接')
            ->hideOnIndex()
            ->setHelp('属性值对应的图片地址')
        ;

        yield IntegerField::new('sortOrder', '排序权重')
            ->hideOnIndex()
            ->setHelp('数值越大排序越靠前')
        ;

        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AttributeStatus::class])
            ->formatValue(function ($value) {
                return $value instanceof AttributeStatus ? $value->label() : '';
            })
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
        // 状态筛选
        $statusChoices = [];
        foreach (AttributeStatus::cases() as $case) {
            $statusChoices[$case->label()] = $case->value;
        }

        return $filters
            ->add(EntityFilter::new('attribute', '所属属性'))
            ->add(TextFilter::new('code', '属性值编码'))
            ->add(TextFilter::new('value', '属性值'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity')
            ->leftJoin('entity.attribute', 'attribute')
            ->addSelect('attribute')
            ->orderBy('attribute.sortOrder', 'DESC')
            ->addOrderBy('entity.sortOrder', 'DESC')
            ->addOrderBy('entity.id', 'DESC')
        ;
    }
}
