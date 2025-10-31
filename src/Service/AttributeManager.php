<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Entity\AttributeValue;
use Tourze\ProductAttributeBundle\Enum\AttributeInputType;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;
use Tourze\ProductAttributeBundle\Enum\AttributeValueType;
use Tourze\ProductAttributeBundle\Exception\AttributeException;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;
use Tourze\ProductAttributeBundle\Repository\AttributeValueRepository;

class AttributeManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AttributeRepository $attributeRepository,
        private readonly AttributeValueRepository $attributeValueRepository,
    ) {
    }

    /**
     * 创建属性
     */
    /**
     * @param array<string, mixed> $data
     */
    public function createAttribute(array $data): Attribute
    {
        // 检查编码唯一性
        assert(isset($data['code']) && is_string($data['code']));
        if ($this->attributeRepository->isCodeExists($data['code'])) {
            throw AttributeException::codeAlreadyExists($data['code']);
        }

        $attribute = new Attribute();
        $this->updateAttributeFromArray($attribute, $data);

        $this->entityManager->persist($attribute);
        $this->entityManager->flush();

        // 如果有预定义值，批量创建
        if (isset($data['values'])) {
            /** @var array<array<string, mixed>> $valuesData */
            $valuesData = $data['values'];
            assert(is_array($valuesData));
            $this->attributeValueRepository->batchCreate($attribute, $valuesData);
        }

        return $attribute;
    }

    /**
     * 更新属性
     */
    /**
     * @param array<string, mixed> $data
     */
    public function updateAttribute(Attribute $attribute, array $data): Attribute
    {
        // 检查编码唯一性
        if (isset($data['code'])) {
            $code = $data['code'];
            assert(is_string($code));
            if ($code !== $attribute->getCode() && $this->attributeRepository->isCodeExists($code, $attribute->getId())) {
                throw AttributeException::codeAlreadyExists($code);
            }
        }

        $this->updateAttributeFromArray($attribute, $data);

        $this->entityManager->flush();

        return $attribute;
    }

    /**
     * 删除属性
     */
    public function deleteAttribute(Attribute $attribute): void
    {
        // 软删除，仅更新状态
        $attribute->setStatus(AttributeStatus::INACTIVE);
        $this->entityManager->flush();
    }

    /**
     * 激活属性
     */
    public function activateAttribute(Attribute $attribute): void
    {
        $attribute->setStatus(AttributeStatus::ACTIVE);
        $this->entityManager->flush();
    }

    /**
     * 停用属性
     */
    public function deactivateAttribute(Attribute $attribute): void
    {
        $attribute->setStatus(AttributeStatus::INACTIVE);
        $this->entityManager->flush();
    }

    /**
     * 添加属性值
     */
    /**
     * @param array<string, mixed> $data
     */
    public function addAttributeValue(Attribute $attribute, array $data): AttributeValue
    {
        // 检查编码唯一性
        assert(isset($data['code']) && is_string($data['code']));
        if ($this->attributeValueRepository->isCodeExists($attribute, $data['code'])) {
            throw AttributeException::valueCodeAlreadyExists($data['code']);
        }

        $value = new AttributeValue();
        $value->setAttribute($attribute);
        $this->updateAttributeValueFromArray($value, $data);

        $this->entityManager->persist($value);
        $this->entityManager->flush();

        return $value;
    }

    /**
     * 更新属性值
     */
    /**
     * @param array<string, mixed> $data
     */
    public function updateAttributeValue(AttributeValue $value, array $data): AttributeValue
    {
        // 检查编码唯一性
        if (isset($data['code'])) {
            $code = $data['code'];
            assert(is_string($code));
            if ($code !== $value->getCode()) {
                $attribute = $value->getAttribute();
                if (null !== $attribute && $this->attributeValueRepository->isCodeExists($attribute, $code, $value->getId())) {
                    throw AttributeException::valueCodeAlreadyExists($code);
                }
            }
        }

        $this->updateAttributeValueFromArray($value, $data);

        $this->entityManager->flush();

        return $value;
    }

    /**
     * 删除属性值
     */
    public function deleteAttributeValue(AttributeValue $value): void
    {
        // 软删除
        $value->setStatus(AttributeStatus::INACTIVE);
        $this->entityManager->flush();
    }

    /**
     * 批量导入属性值
     */
    /**
     * @param array<array<string, mixed>> $valuesData
     * @return array<AttributeValue>
     */
    public function importAttributeValues(Attribute $attribute, array $valuesData): array
    {
        $imported = [];

        foreach ($valuesData as $data) {
            // 查找现有值
            $code = $data['code'];
            assert(is_string($code));
            $value = $this->attributeValueRepository->findByAttributeAndCode($attribute, $code);

            if (null === $value) {
                // 创建新值
                $value = new AttributeValue();
                $value->setAttribute($attribute);
                $this->entityManager->persist($value);
            }

            $this->updateAttributeValueFromArray($value, $data);
            $imported[] = $value;
        }

        $this->entityManager->flush();

        return $imported;
    }

    /**
     * 从数组更新属性
     */
    /**
     * @param array<string, mixed> $data
     */
    private function updateAttributeFromArray(Attribute $attribute, array $data): void
    {
        $this->updateBasicAttributeFields($attribute, $data);
        $this->updateAttributeFlags($attribute, $data);
        $this->updateAttributeMetadata($attribute, $data);
    }

    /**
     * 更新基础属性字段
     */
    /**
     * @param array<string, mixed> $data
     */
    private function updateBasicAttributeFields(Attribute $attribute, array $data): void
    {
        $this->setBasicFieldValues($attribute, $data);
        $this->setEnumFieldValues($attribute, $data);
    }

    /**
     * 设置基础字段值
     */
    /**
     * @param array<string, mixed> $data
     */
    private function setBasicFieldValues(Attribute $attribute, array $data): void
    {
        if (isset($data['code'])) {
            assert(is_string($data['code']));
            $attribute->setCode($data['code']);
        }

        if (isset($data['name'])) {
            assert(is_string($data['name']));
            $attribute->setName($data['name']);
        }

        if (isset($data['valueType'])) {
            assert(is_string($data['valueType']));
            $attribute->setValueType(AttributeValueType::from($data['valueType']));
        }

        if (isset($data['inputType'])) {
            assert(is_string($data['inputType']));
            $attribute->setInputType(AttributeInputType::from($data['inputType']));
        }

        if (isset($data['sortOrder'])) {
            assert(is_int($data['sortOrder']));
            $attribute->setSortOrder($data['sortOrder']);
        }

        if (isset($data['config'])) {
            /** @var array<string, mixed>|null $config */
            $config = $data['config'];
            assert(is_array($config) || null === $config);
            $attribute->setConfig($config);
        }
    }

    /**
     * 设置枚举字段值
     */
    /**
     * @param array<string, mixed> $data
     */
    private function setEnumFieldValues(Attribute $attribute, array $data): void
    {
        if (isset($data['type'])) {
            $typeValue = $data['type'];
            if ($typeValue instanceof AttributeType) {
                $type = $typeValue;
            } else {
                assert(is_string($typeValue) || is_int($typeValue));
                $type = AttributeType::from($typeValue);
            }
            $attribute->setType($type);
        }

        if (isset($data['status'])) {
            $statusValue = $data['status'];
            if ($statusValue instanceof AttributeStatus) {
                $status = $statusValue;
            } else {
                assert(is_string($statusValue) || is_int($statusValue));
                $status = AttributeStatus::from($statusValue);
            }
            $attribute->setStatus($status);
        }
    }

    /**
     * 更新属性标志位
     */
    /**
     * @param array<string, mixed> $data
     */
    private function updateAttributeFlags(Attribute $attribute, array $data): void
    {
        if (isset($data['isRequired'])) {
            assert(is_bool($data['isRequired']));
            $attribute->setIsRequired($data['isRequired']);
        }

        if (isset($data['isMultiple'])) {
            assert(is_bool($data['isMultiple']));
            $attribute->setIsMultiple($data['isMultiple']);
        }

        if (isset($data['isSearchable'])) {
            assert(is_bool($data['isSearchable']));
            $attribute->setIsSearchable($data['isSearchable']);
        }

        if (isset($data['isFilterable'])) {
            assert(is_bool($data['isFilterable']));
            $attribute->setIsFilterable($data['isFilterable']);
        }
    }

    /**
     * 更新属性元数据
     */
    /**
     * @param array<string, mixed> $data
     */
    private function updateAttributeMetadata(Attribute $attribute, array $data): void
    {
        // 预留扩展点，用于更新其他元数据字段
    }

    /**
     * 从数组更新属性值
     */
    /**
     * @param array<string, mixed> $data
     */
    private function updateAttributeValueFromArray(AttributeValue $value, array $data): void
    {
        if (isset($data['code'])) {
            assert(is_string($data['code']));
            $value->setCode($data['code']);
        }

        if (isset($data['value'])) {
            assert(is_string($data['value']));
            $value->setValue($data['value']);
        }

        if (isset($data['aliases'])) {
            /** @var array<string>|null $aliases */
            $aliases = $data['aliases'];
            assert(is_array($aliases) || null === $aliases);
            $value->setAliases($aliases);
        }

        if (isset($data['colorValue'])) {
            $colorValue = $data['colorValue'];
            assert(is_string($colorValue));
            $value->setColorValue($colorValue);
        }

        if (isset($data['imageUrl'])) {
            $imageUrl = $data['imageUrl'];
            assert(is_string($imageUrl));
            $value->setImageUrl($imageUrl);
        }

        if (isset($data['sortOrder'])) {
            assert(is_int($data['sortOrder']));
            $value->setSortOrder($data['sortOrder']);
        }

        if (isset($data['status'])) {
            $statusValue = $data['status'];
            if ($statusValue instanceof AttributeStatus) {
                $status = $statusValue;
            } else {
                assert(is_string($statusValue) || is_int($statusValue));
                $status = AttributeStatus::from($statusValue);
            }
            $value->setStatus($status);
        }
    }
}
