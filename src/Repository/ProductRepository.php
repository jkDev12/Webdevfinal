<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findAllWithStock(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.stocks', 's')
            ->addSelect('s')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(?string $query = null, ?int $creatorId = null, ?string $availability = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.stocks', 's')->addSelect('s')
            ->leftJoin('p.createdBy', 'c')->addSelect('c')
            ->orderBy('p.id', 'DESC');

        if (null !== $query && '' !== $query) {
            $qb->andWhere('LOWER(p.name) LIKE :q')->setParameter('q', '%'.mb_strtolower($query).'%');
        }
        if (null !== $creatorId) {
            $qb->andWhere('c.id = :creator')->setParameter('creator', $creatorId);
        }

        $hasStock = 'EXISTS (SELECT 1 FROM '.ProductStock::class.' s2 WHERE s2.product = p AND s2.quantity > 0)';
        if ('in_stock' === $availability) {
            $qb->andWhere($hasStock);
        } elseif ('sold_out' === $availability) {
            $qb->andWhere('NOT '.$hasStock);
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return $this->count([]);
    }
}
