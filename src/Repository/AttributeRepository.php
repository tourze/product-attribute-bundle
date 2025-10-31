<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAttributeBundle\Entity\Attribute;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;
use Tourze\ProductAttributeBundle\Enum\AttributeType;

/**
 * @extends ServiceEntityRepository<Attribute>
 */
#[AsRepository(entityClass: Attribute::class)]
class AttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attribute::class);
    }

    /**
     * 查找活跃的属性
     *
     * @return Attribute[]
     */
    public function findActiveAttributes(): array
    {
        /** @var array<Attribute> */
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', AttributeStatus::ACTIVE)
            ->orderBy('a.sortOrder', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据类型查找属性
     *
     * @return Attribute[]
     */
    public function findByType(AttributeType $type): array
    {
        /** @var array<Attribute> */
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andWhere('a.status = :status')
            ->setParameter('type', $type)
            ->setParameter('status', AttributeStatus::ACTIVE)
            ->orderBy('a.sortOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找销售属性
     *
     * @return Attribute[]
     */
    public function findSalesAttributes(): array
    {
        return $this->findByType(AttributeType::SALES);
    }

    /**
     * 查找非销售属性
     *
     * @return Attribute[]
     */
    public function findNonSalesAttributes(): array
    {
        return $this->findByType(AttributeType::NON_SALES);
    }

    /**
     * 根据编码查找属性
     */
    public function findByCode(string $code): ?Attribute
    {
        /** @var Attribute|null */
        return $this->createQueryBuilder('a')
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据编码列表查找属性
     *
     * @param string[] $codes
     * @return Attribute[]
     */
    public function findByCodes(array $codes): array
    {
        if (0 === count($codes)) {
            return [];
        }

        /** @var array<Attribute> */
        return $this->createQueryBuilder('a')
            ->andWhere('a.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 创建搜索查询构建器
     */
    public function createSearchQueryBuilder(string $search = ''): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        if ('' !== $search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'a.code LIKE :search',
                    'a.name LIKE :search'
                )
            )
                ->setParameter('search', '%' . $search . '%')
            ;
        }

        return $qb;
    }

    /**
     * 检查编码是否已存在
     */
    public function isCodeExists(string $code, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 保存属性实体
     */
    public function save(Attribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除属性实体
     */
    public function remove(Attribute $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
