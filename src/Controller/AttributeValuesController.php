<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;

final class AttributeValuesController extends AbstractController
{
    public function __construct(
        private readonly AttributeRepository $attributeRepository,
    ) {
    }

    #[Route(path: '/api/product-attribute/attributes/{id}/values', name: 'api_attributes_values', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $attribute = $this->attributeRepository->find($id);

        if (null === $attribute) {
            return $this->json(['error' => 'Attribute not found'], Response::HTTP_NOT_FOUND);
        }

        // 只获取活跃状态的属性值
        $values = $attribute->getValues()->filter(function ($value) {
            return $value->isActive();
        })->toArray();

        // 按 sortOrder 排序（DESC，数值大的在前）
        usort($values, function ($a, $b) {
            return $b->getSortOrder() <=> $a->getSortOrder();
        });

        return $this->json([
            'data' => array_map(fn ($value) => [
                'id' => $value->getId(),
                'code' => $value->getCode(),
                'value' => $value->getValue(),
                'label' => $value->getLabel(),
                'sortOrder' => $value->getSortOrder(),
                'status' => $value->getStatus()->value,
            ], $values),
        ]);
    }
}
