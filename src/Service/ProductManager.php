<?php

namespace App\Service;

use App\Dto\ProductData;
use App\Entity\Product;
use App\Entity\ProductStock;
use App\Entity\StockMovement;
use App\Entity\User;
use App\Exception\StoreException;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductManager
{
    private const UPLOAD_PREFIX = 'uploads/products/';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderRepository $orders,
        private readonly StockService $stockLog,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%/public/uploads/products')]
        private readonly string $uploadDir,
    ) {
    }

    public function create(ProductData $data, User $owner): Product
    {
        $file = $data->image ?? throw new StoreException('Please choose a product image.');
        $imagePath = $this->storeImage($file, (string) $data->name);

        try {
            $product = (new Product())
                ->setName(trim((string) $data->name))
                ->setImage($imagePath)
                ->setPrice((string) $data->price)
                ->setCreatedBy($owner);

            foreach (ProductStock::SIZES as $size) {
                $quantity = max((int) ($data->stock[$size] ?? 0), 0);
                $product->addStock((new ProductStock())->setSize($size)->setQuantity($quantity));
                $this->stockLog->record($product, $size, 0, $quantity, $owner, StockMovement::SOURCE_PRODUCT_CREATED);
            }

            $this->em->persist($product);
            $this->em->flush();

            return $product;
        } catch (\Throwable $e) {
            $this->removeImage($imagePath);
            error_log('Add product failed: '.$e->getMessage());
            throw new StoreException('Something went wrong while saving the product.');
        }
    }

    public function update(Product $product, ProductData $data, User $actor): void
    {
        $oldImage = $product->getImage();
        $newImage = $data->image ? $this->storeImage($data->image, (string) $data->name) : null;

        try {
            $product->setName(trim((string) $data->name))->setPrice((string) $data->price);
            if (null !== $newImage) {
                $product->setImage($newImage);
            }

            foreach (ProductStock::SIZES as $size) {
                $quantity = max((int) ($data->stock[$size] ?? 0), 0);
                $found = false;
                foreach ($product->getStocks() as $row) {
                    if ($row->getSize() === $size) {
                        $this->stockLog->record($product, $size, $row->getQuantity(), $quantity, $actor, StockMovement::SOURCE_PRODUCT_EDIT);
                        $row->setQuantity($quantity);
                        $found = true;
                    }
                }
                if (!$found) {
                    $product->addStock((new ProductStock())->setSize($size)->setQuantity($quantity));
                    $this->stockLog->record($product, $size, 0, $quantity, $actor, StockMovement::SOURCE_PRODUCT_EDIT);
                }
            }

            $this->em->flush();
        } catch (\Throwable $e) {
            if (null !== $newImage) {
                $this->removeImage($newImage);
            }
            error_log('Update product failed: '.$e->getMessage());
            throw new StoreException('Something went wrong while saving the product.');
        }

        if (null !== $newImage) {
            $this->removeImage($oldImage);
        }
    }

    public function delete(Product $product): void
    {
        if ($this->orders->countForProduct((int) $product->getId()) > 0) {
            throw new StoreException(sprintf('"%s" already has orders, so it cannot be deleted. Set its stock to 0 instead.', $product->getName()));
        }

        $image = $product->getImage();
        $this->em->remove($product);
        $this->em->flush();
        $this->removeImage($image);
    }

    private function storeImage(UploadedFile $file, string $name): string
    {
        $ext = $file->guessExtension() ?: strtolower($file->getClientOriginalExtension());
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'product';
        $filename = $slug.'-'.bin2hex(random_bytes(4)).'.'.$ext;

        try {
            $file->move($this->uploadDir, $filename);
        } catch (\Throwable) {
            throw new StoreException('Could not save the uploaded image. Please try again.');
        }

        return self::UPLOAD_PREFIX.$filename;
    }

    private function removeImage(string $imagePath): void
    {
        if (str_starts_with($imagePath, self::UPLOAD_PREFIX)) {
            $this->filesystem->remove($this->uploadDir.'/'.substr($imagePath, strlen(self::UPLOAD_PREFIX)));
        }
    }
}
