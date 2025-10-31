<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;

final class AttributeShowController extends AbstractController
{
    public function __construct(
        private readonly AttributeRepository $attributeRepository,
    ) {
    }

    #[Route(path: '/api/product-attribute/attributes/{id}', name: 'api_attributes_show', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $attribute = $this->attributeRepository->find($id);

        if (null === $attribute) {
            return $this->json(['error' => 'Attribute not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'data' => [
                'id' => $attribute->getId(),
                'code' => $attribute->getCode(),
                'name' => $attribute->getName(),
                'type' => $attribute->getType()->value,
                'valueType' => $attribute->getValueType()->value,
                'inputType' => $attribute->getInputType()->value,
                'unit' => $attribute->getUnit(),
                'isRequired' => $attribute->isRequired(),
                'isSearchable' => $attribute->isSearchable(),
                'isFilterable' => $attribute->isFilterable(),
                'isMultiple' => $attribute->isMultiple(),
                'sortOrder' => $attribute->getSortOrder(),
                'config' => $attribute->getConfig(),
                'validationRules' => $attribute->getValidationRules(),
                'status' => $attribute->getStatus()->value,
                'values' => array_map(fn ($value) => [
                    'id' => $value->getId(),
                    'code' => $value->getCode(),
                    'value' => $value->getValue(),
                    'label' => $value->getLabel(),
                    'sortOrder' => $value->getSortOrder(),
                    'status' => $value->getStatus()->value,
                ], $attribute->getValues()->toArray()),
            ],
        ]);
    }
}
