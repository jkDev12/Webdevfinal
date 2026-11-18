<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Entity\User;
use App\Exception\StoreException;
use App\Repository\ProductStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;

final class StockService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductStockRepository $stocks,
    ) {
    }

    /** @throws StoreException */
    public function add(int $productId, string $size, int $quantity, User $actor): int
    {
        return $this->adjust($productId, $size, $quantity, $actor, true);
    }

    /**
     * Stock never goes below zero.
     *
     * @return int units actually removed
     *
     * @throws StoreException
     */
    public function reduce(int $productId, string $size, int $quantity, User $actor): int
    {
        return $this->adjust($productId, $size, $quantity, $actor, false);
    }

    /** Queue a history row (the caller flushes). Does nothing when the quantity did not change. */
    public function record(Product $product, string $size, int $before, int $after, User $actor, string $source): void
    {
        $movement = StockMovement::record($product, $size, $before, $after, $actor, $source);
        if (null !== $movement) {
            $this->em->persist($movement);
        }
    }

    private function adjust(int $productId, string $size, int $quantity, User $actor, bool $add): int
    {
        return $this->em->wrapInTransaction(function () use ($productId, $size, $quantity, $actor, $add): int {
            $product = $this->em->find(Product::class, $productId) ?? throw new StoreException('That product could not be found.');

            try {
                $before = $this->stocks->quantityFor($productId, $size);
            } catch (NoResultException) {
                throw new StoreException('That product has no stock row for that size.');
            }

            $add ? $this->stocks->increase($productId, $size, $quantity) : $this->stocks->decreaseToZeroAtLeast($productId, $size, $quantity);
            $after = $this->stocks->quantityFor($productId, $size);

            $this->record($product, $size, $before, $after, $actor, StockMovement::SOURCE_ADJUSTMENT);
            $this->em->flush();

            return abs($after - $before);
        });
    }
}
