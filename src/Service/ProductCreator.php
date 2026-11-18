<?php

namespace App\Service;

use App\Dto\NewProductInput;
use App\Entity\Product;
use App\Entity\ProductStock;
use App\Entity\StockMovement;
use App\Entity\User;
use App\Exception\StoreException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final class ProductCreator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Filesystem $filesystem,
        private readonly StockService $stockLog,
        #[Autowire('%kernel.project_dir%/public/uploads/products')]
        private readonly string $uploadDir,
    ) {
    }

    /** @throws StoreException */
    public function create(NewProductInput $input, User $actor): Product
    {
        $file = $input->image ?? throw new StoreException('Please choose a product image.');

        $ext = $file->guessExtension() ?: strtolower($file->getClientOriginalExtension());
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($input->name)), '-') ?: 'product';
        $filename = $slug.'-'.bin2hex(random_bytes(4)).'.'.$ext;

        try {
            $file->move($this->uploadDir, $filename);
        } catch (\Throwable) {
            throw new StoreException('Could not save the uploaded image. Please try again.');
        }

        try {
            $product = (new Product())
                ->setName($input->name)
                ->setImage('uploads/products/'.$filename)
                ->setPrice($input->price)
                ->setCreatedBy($actor);

            foreach (ProductStock::SIZES as $size) {
                $quantity = $input->startingStock[$size] ?? 0;
                $product->addStock((new ProductStock())->setSize($size)->setQuantity($quantity));
                $this->stockLog->record($product, $size, 0, $quantity, $actor, StockMovement::SOURCE_PRODUCT_CREATED);
            }

            $this->em->persist($product);
            $this->em->flush();

            return $product;
        } catch (\Throwable $e) {
            $this->filesystem->remove($this->uploadDir.'/'.$filename);
            error_log('Add product failed: '.$e->getMessage());
            throw new StoreException('Something went wrong while saving the product.');
        }
    }
}
