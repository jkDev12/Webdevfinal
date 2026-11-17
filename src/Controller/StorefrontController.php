<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StorefrontController extends AbstractController
{
    #[Route('/', name: 'app_storefront')]
    public function index(ProductRepository $products): Response
    {
        $catalog = $products->findAllWithStock();

        $productsJson = json_encode(
            array_map(static fn ($p) => [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'image' => $p->getImage(),
                'price' => $p->getPrice(),
                'sizes' => $p->sizeQuantities(),
            ], $catalog),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );

        return $this->render('storefront/index.html.twig', [
            'products' => $catalog,
            'products_json' => $productsJson,
        ]);
    }
}
