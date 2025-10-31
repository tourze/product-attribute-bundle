# Product Attribute Bundle

[English](README.md) | [中文](README.zh-CN.md)

商品属性管理模块，提供商品属性的结构化管理功能。

## 功能特性

### 核心功能
- **属性管理** - 创建和管理商品属性（销售属性、非销售属性）
- **属性值管理** - 管理属性的可选值（枚举类型属性）
- **属性分组** - 将属性按业务逻辑分组管理
- **类目属性关联** - 将属性与商品类目关联，支持属性继承
- **SPU/SKU属性** - 管理标准产品单位和库存单位的属性值

### 管理后台
- **EasyAdmin 集成** - 提供完整的后台管理界面
- **属性CRUD操作** - 增删改查属性配置
- **筛选和搜索** - 按类型、状态等条件筛选属性
- **表单验证** - 完整的数据验证和错误提示

### API接口
- **属性列表API** - 获取商品属性列表
- **属性详情API** - 获取单个属性的详细信息
- **属性值查询** - 查询属性的可选值
- **属性搜索** - 支持属性名称和编码搜索

## 安装

```bash
composer require tourze/product-attribute-bundle
```

## 配置

### 注册Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\ProductAttributeBundle\ProductAttributeBundle::class => ['all' => true],
];
```

### 数据库迁移

```bash
php bin/console doctrine:migrations:migrate
```

### 加载测试数据（可选）

```bash
php bin/console doctrine:fixtures:load --group=ProductAttribute
```

## 使用示例

### 属性管理

```php
use Tourze\ProductAttributeBundle\Service\AttributeManager;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeType;

// 创建属性
$attributeManager = $container->get(AttributeManager::class);

$attributeData = [
    'code' => 'color',
    'name' => '颜色',
    'type' => AttributeType::SALES,
    'valueType' => 'single',
    'inputType' => 'select'
];

$attribute = $attributeManager->createFromArray($attributeData);
```

### 产品属性管理

```php
use Tourze\ProductAttributeBundle\Service\ProductAttributeManager;

$productManager = $container->get(ProductAttributeManager::class);

// 为SPU设置属性值
$spuAttributeData = [
    [
        'attribute' => $colorAttribute,
        'value_ids' => [1, 2, 3], // 红色、绿色、蓝色
    ]
];

$productManager->setSpuAttributes($spu, $spuAttributeData);
```

### API使用

```php
// 获取属性列表
GET /api/attributes

// 获取特定属性详情
GET /api/attributes/{id}

// 获取属性可选值
GET /api/attributes/{id}/values

// 搜索属性
GET /api/attributes/search?q=颜色
```

## 实体结构

### 核心实体

- **Attribute** - 属性主表
- **AttributeValue** - 属性值表（枚举类型属性的可选值）
- **AttributeGroup** - 属性分组表
- **CategoryAttribute** - 类目属性关联表
- **SpuAttribute** - SPU属性值表
- **SkuAttribute** - SKU属性值表

### 枚举类型

- **AttributeType** - 属性类型（销售属性、非销售属性）
- **AttributeValueType** - 值类型（单选、多选、文本等）
- **AttributeInputType** - 输入类型（下拉框、输入框、多选框等）
- **AttributeStatus** - 属性状态（启用、禁用）

## 扩展开发

### 自定义属性类型

```php
// 创建自定义属性类型枚举
enum CustomAttributeType: string
{
    case SPECIFICATION = 'specification';
    case PARAMETER = 'parameter';
}
```

### 自定义验证规则

```php
use Symfony\Component\Validator\Constraint;

#[Attribute]
class CustomAttributeConstraint extends Constraint
{
    public string $message = '自定义验证失败';
}
```

## 测试

```bash
# 运行所有测试
./vendor/bin/phpunit packages/product-attribute-bundle/tests

# 运行特定测试
./vendor/bin/phpunit packages/product-attribute-bundle/tests/Entity/AttributeTest.php

# 代码质量检查
./vendor/bin/phpstan analyse packages/product-attribute-bundle --level=8
```

## 许可证

MIT License. 请查看 [LICENSE](LICENSE) 文件了解更多信息。
