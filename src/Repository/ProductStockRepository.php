<?php

namespace App\Repository;

use App\Entity\ProductStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductStock::class);
    }

    public function increase(int $productId, string $size, int $quantity): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'UPDATE '.ProductStock::class.' s SET s.quantity = s.quantity + :qty
             WHERE s.product = :pid AND s.size = :size'
        )->setParameters(['qty' => $quantity, 'pid' => $productId, 'size' => $size])->execute();
    }

    public function decreaseToZeroAtLeast(int $productId, string $size, int $quantity): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'UPDATE '.ProductStock::class.' s
             SET s.quantity = CASE WHEN s.quantity > :qty THEN s.quantity - :qty ELSE 0 END
             WHERE s.product = :pid AND s.size = :size'
        )->setParameters(['qty' => $quantity, 'pid' => $productId, 'size' => $size])->execute();
    }

    public function reserveOne(int $productId, string $size): bool
    {
        return 1 === (int) $this->getEntityManager()->createQuery(
            'UPDATE '.ProductStock::class.' s SET s.quantity = s.quantity - 1
             WHERE s.product = :pid AND s.size = :size AND s.quantity >= 1'
        )->setParameters(['pid' => $productId, 'size' => $size])->execute();
    }

    public function quantityFor(int $productId, string $size): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('s.quantity')
            ->where('s.product = :pid AND s.size = :size')
            ->setParameter('pid', $productId)
            ->setParameter('size', $size)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
