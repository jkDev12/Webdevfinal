<?php

namespace App\Service;

use App\Repository\ProductStockRepository;

final class StockService
{
    public function __construct(private readonly ProductStockRepository $stocks)
    {
    }

    public function add(int $productId, string $size, int $quantity): void
    {
        $this->stocks->increase($productId, $size, $quantity);
    }

    public function reduce(int $productId, string $size, int $quantity): void
    {
        $this->stocks->decreaseToZeroAtLeast($productId, $size, $quantity);
    }
}
