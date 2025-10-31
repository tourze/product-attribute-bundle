<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Repository\AttributeRepository;

final class AttributeListController extends AbstractController
{
    public function __construct(
        private readonly AttributeRepository $attributeRepository,
    ) {
    }

    #[Route(path: '/api/product-attribute/attributes', name: 'api_attributes_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $pagination = $this->extractPagination($request);

        $attributes = $this->getFilteredAttributes($filters);
        $paginatedAttributes = $this->paginateResults($attributes, $pagination);

        $response = [
            'data' => array_map(fn ($attr) => $this->transformAttribute($attr), $paginatedAttributes),
        ];

        if ($this->shouldIncludePagination($pagination, count($attributes))) {
            $response['pagination'] = $this->buildPaginationData($pagination, count($attributes));
        }

        return $this->json($response);
    }

    /**
     * @return array<string, string|null>
     */
    private function extractFilters(Request $request): array
    {
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        return [
            'type' => (is_string($type) && '' !== $type) ? $type : null,
            'status' => (is_string($status) && '' !== $status) ? $status : 'active',
            'search' => (is_string($search) && '' !== $search) ? $search : null,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function extractPagination(Request $request): array
    {
        return [
            'page' => (int) $request->query->get('page', 1),
            'limit' => (int) $request->query->get('limit', 20),
        ];
    }

    /**
     * @param array<string, string|null> $filters
     * @return array<int, Attribute>
     */
    private function getFilteredAttributes(array $filters): array
    {
        if (null !== $filters['search']) {
            return $this->getSearchAttributes($filters);
        }

        $criteria = array_filter([
            'type' => $filters['type'],
            'status' => $filters['status'],
        ], fn ($value) => null !== $value);

        return $this->attributeRepository->findBy($criteria, ['sortOrder' => 'ASC']);
    }

    /**
     * @param array<string, string|null> $filters
     * @return list<Attribute>
     */
    private function getSearchAttributes(array $filters): array
    {
        $qb = $this->attributeRepository->createQueryBuilder('a')
            ->where('a.code LIKE :search OR a.name LIKE :search')
            ->setParameter('search', '%' . $filters['search'] . '%')
            ->orderBy('a.sortOrder', 'ASC')
        ;

        if (null !== $filters['type']) {
            $qb->andWhere('a.type = :type')
                ->setParameter('type', $filters['type'])
            ;
        }

        $qb->andWhere('a.status = :status')
            ->setParameter('status', $filters['status'])
        ;

        /** @var list<Attribute> */
        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<int, Attribute> $attributes
     * @param array<string, int> $pagination
     * @return array<int, Attribute>
     */
    private function paginateResults(array $attributes, array $pagination): array
    {
        $offset = ($pagination['page'] - 1) * $pagination['limit'];

        return array_slice($attributes, $offset, $pagination['limit']);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformAttribute(Attribute $attr): array
    {
        return [
            'id' => $attr->getId(),
            'code' => $attr->getCode(),
            'name' => $attr->getName(),
            'type' => $attr->getType()->value,
            'valueType' => $attr->getValueType()->value,
            'inputType' => $attr->getInputType()->value,
            'unit' => $attr->getUnit(),
            'isRequired' => $attr->isRequired(),
            'isSearchable' => $attr->isSearchable(),
            'isFilterable' => $attr->isFilterable(),
            'isMultiple' => $attr->isMultiple(),
            'sortOrder' => $attr->getSortOrder(),
            'status' => $attr->getStatus()->value,
        ];
    }

    /**
     * @param array<string, int> $pagination
     */
    private function shouldIncludePagination(array $pagination, int $total): bool
    {
        return $pagination['page'] > 1 || $pagination['limit'] < $total;
    }

    /**
     * @param array<string, int> $pagination
     * @return array<string, int>
     */
    private function buildPaginationData(array $pagination, int $total): array
    {
        return [
            'current_page' => $pagination['page'],
            'per_page' => $pagination['limit'],
            'total' => $total,
            'last_page' => (int) ceil($total / $pagination['limit']),
        ];
    }
}
