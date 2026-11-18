<?php

namespace App\Repository;

use App\Entity\StockMovement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockMovement::class);
    }

    /** @return StockMovement[] newest first */
    public function search(
        ?string $query = null,
        ?string $type = null,
        ?string $size = null,
        ?int $actorId = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        int $limit = 200,
    ): array {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit);

        if (null !== $query && '' !== $query) {
            $qb->andWhere('LOWER(m.productName) LIKE :q OR LOWER(m.actorName) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($query).'%');
        }
        if (null !== $type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }
        if (null !== $size) {
            $qb->andWhere('m.size = :size')->setParameter('size', $size);
        }
        if (null !== $actorId) {
            $qb->andWhere('IDENTITY(m.actor) = :actor')->setParameter('actor', $actorId);
        }
        if (null !== $from) {
            $qb->andWhere('m.createdAt >= :from')->setParameter('from', $from);
        }
        if (null !== $to) {
            $qb->andWhere('m.createdAt < :to')->setParameter('to', $to->modify('+1 day'));
        }

        return $qb->getQuery()->getResult();
    }
}
