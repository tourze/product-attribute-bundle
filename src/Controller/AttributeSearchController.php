<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;

final class AttributeSearchController extends AbstractController
{
    public function __construct(
        private readonly AttributeRepository $attributeRepository,
    ) {
    }

    #[Route(path: '/api/product-attribute/attributes/search', name: 'api_attributes_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type');

        if (null === $query || '' === $query) {
            return $this->json(['data' => []]);
        }

        $qb = $this->attributeRepository->createQueryBuilder('a')
            ->where('a.code LIKE :query OR a.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.sortOrder', 'ASC')
        ;

        if (null !== $type && '' !== $type) {
            $qb->andWhere('a.type = :type')
                ->setParameter('type', $type)
            ;
        }

        $result = $qb->getQuery()->getResult();
        $attributes = is_array($result) ? $result : [];

        return $this->json([
            'data' => array_map(function ($attr) {
                if (!$attr instanceof Attribute) {
                    return [];
                }

                $id = $attr->getId();
                $code = $attr->getCode();
                $name = $attr->getName();
                $type = $attr->getType();

                return [
                    'id' => is_scalar($id) ? $id : null,
                    'code' => $code,
                    'name' => $name,
                    'type' => $type->value,
                ];
            }, $attributes),
        ]);
    }
}
