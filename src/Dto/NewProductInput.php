<?php

namespace App\Dto;

use App\Entity\ProductStock;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class NewProductInput
{
    /** @param array<string, int> $startingStock size => units */
    public function __construct(
        #[Assert\NotBlank(message: 'Please enter a product name.')]
        public readonly string $name = '',

        #[Assert\Sequentially([
            new Assert\Type(type: 'numeric', message: 'Please enter a valid, non-negative price.'),
            new Assert\PositiveOrZero(message: 'Please enter a valid, non-negative price.'),
        ])]
        public readonly string $price = '',

        #[Assert\NotNull(message: 'Please choose a product image.')]
        #[Assert\File(
            maxSize: '5M',
            extensions: ['jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp']],
            extensionsMessage: 'Please upload a JPG, PNG, or WEBP image.',
        )]
        public readonly ?UploadedFile $image = null,

        public readonly array $startingStock = [],
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $stock = [];
        foreach (ProductStock::SIZES as $size) {
            $stock[$size] = max($request->request->getInt('stock_'.$size), 0);
        }

        return new self(
            name: trim((string) $request->request->get('name', '')),
            price: trim((string) $request->request->get('price', '')),
            image: $request->files->get('image'),
            startingStock: $stock,
        );
    }
}
