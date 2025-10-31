<?php

declare(strict_types=1);

namespace Tourze\ProductAttributeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAttributeBundle\Entity\AttributeGroup;
use Tourze\ProductAttributeBundle\Enum\AttributeStatus;

/**
 * @extends ServiceEntityRepository<AttributeGroup>
 */
#[AsRepository(entityClass: AttributeGroup::class)]
class AttributeGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttributeGroup::class);
    }

    /**
     * 查找所有活跃的分组
     *
     * @return AttributeGroup[]
     */
    public function findActiveGroups(): array
    {
        /** @var array<AttributeGroup> */
        return $this->createQueryBuilder('g')
            ->andWhere('g.status = :status')
            ->setParameter('status', AttributeStatus::ACTIVE)
            ->orderBy('g.sortOrder', 'ASC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据编码查找分组
     */
    public function findByCode(string $code): ?AttributeGroup
    {
        /** @var AttributeGroup|null */
        return $this->createQueryBuilder('g')
            ->andWhere('g.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 保存属性分组实体
     */
    public function save(AttributeGroup $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 检查编码是否已存在
     */
    public function isCodeExists(string $code, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->andWhere('g.code = :code')
            ->setParameter('code', $code)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('g.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 删除属性分组实体
     */
    public function remove(AttributeGroup $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
