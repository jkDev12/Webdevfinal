<?php

namespace App\Dto;

use App\Entity\ProductStock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class StockAdjustInput
{
    private const MESSAGE = 'Please choose a product, a size, and a positive quantity.';

    public function __construct(
        #[Assert\Positive(message: self::MESSAGE)]
        public readonly int $productId = 0,

        #[Assert\Choice(choices: ProductStock::SIZES, message: self::MESSAGE)]
        public readonly string $size = '',

        #[Assert\Positive(message: self::MESSAGE)]
        public readonly int $quantity = 0,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            productId: $request->request->getInt('product_id'),
            size: strtoupper(trim((string) $request->request->get('size', ''))),
            quantity: $request->request->getInt('quantity'),
        );
    }
}
