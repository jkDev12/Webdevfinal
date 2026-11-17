<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.product', 'p')->addSelect('p')
            ->join('o.user', 'u')->addSelect('u')
            ->orderBy('o.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function countForUser(int $userId): int
    {
        return $this->count(['user' => $userId]);
    }

    public function countForProduct(int $productId): int
    {
        return $this->count(['product' => $productId]);
    }

    public function markCodPaid(int $orderId): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'UPDATE '.Order::class.' o SET o.paymentStatus = :paid
             WHERE o.id = :id AND o.paymentMethod = :cod AND o.status = :active'
        )->setParameters([
            'paid' => Order::PAYMENT_STATUS_PAID,
            'cod' => Order::PAYMENT_COD,
            'active' => Order::STATUS_ACTIVE,
            'id' => $orderId,
        ])->execute();
    }

    public function cancelIfActive(int $orderId): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'UPDATE '.Order::class.' o SET o.status = :cancelled WHERE o.id = :id AND o.status = :active'
        )->setParameters([
            'cancelled' => Order::STATUS_CANCELLED,
            'active' => Order::STATUS_ACTIVE,
            'id' => $orderId,
        ])->execute();
    }
}
