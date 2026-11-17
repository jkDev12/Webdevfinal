<?php

namespace App\Dto;

use App\Entity\Product;
use App\Entity\ProductStock;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class ProductData
{
    #[Assert\NotBlank(message: 'Please enter a product name.')]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Please enter a valid, non-negative price.'),
        new Assert\Type(type: 'numeric', message: 'Please enter a valid, non-negative price.'),
        new Assert\PositiveOrZero(message: 'Please enter a valid, non-negative price.'),
    ])]
    public ?string $price = null;

    #[Assert\When(
        expression: 'this.imageRequired',
        constraints: [new Assert\NotNull(message: 'Please choose a product image.')],
    )]
    #[Assert\File(
        maxSize: '5M',
        extensions: ['jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp']],
        extensionsMessage: 'Please upload a JPG, PNG, or WEBP image.',
    )]
    public ?UploadedFile $image = null;

    #[Assert\All([new Assert\PositiveOrZero(message: 'Stock cannot be negative.')])]
    public array $stock = [];

    public bool $imageRequired = true;

    public static function fromProduct(Product $product): self
    {
        $data = new self();
        $data->name = $product->getName();
        $data->price = $product->getPrice();
        $data->stock = $product->sizeQuantities();
        $data->imageRequired = false;

        return $data;
    }

    public static function blank(): self
    {
        $data = new self();
        $data->stock = array_fill_keys(ProductStock::SIZES, 0);

        return $data;
    }
}
