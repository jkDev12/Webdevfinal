<?php

namespace App\Controller;

use App\Dto\StockAdjustInput;
use App\Entity\ProductStock;
use App\Entity\User;
use App\Exception\StoreException;
use App\Repository\ProductRepository;
use App\Security\Voter\ProductVoter;
use App\Service\StockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Staff-facing stock updates (post / remove units). Every change is written to the stock history. */
#[Route('/products/stock')]
#[IsGranted('ROLE_STAFF')]
final class StockController extends AbstractController
{
    private const CSRF_ID = 'staff_stock';
    private const INVALID = 'Please choose a product, a size, and a positive quantity.';

    #[Route('', name: 'app_staff_stock', methods: ['GET'])]
    public function index(ProductRepository $products): Response
    {
        return $this->render('stock/index.html.twig', [
            'products' => $products->findAllWithStock(),
            'sizes' => ProductStock::SIZES,
        ]);
    }

    #[Route('/add', name: 'app_staff_stock_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator, ProductRepository $products, StockService $stock, #[CurrentUser] User $me): RedirectResponse
    {
        return $this->adjust(true, $request, $validator, $products, $stock, $me);
    }

    #[Route('/reduce', name: 'app_staff_stock_reduce', methods: ['POST'])]
    public function reduce(Request $request, ValidatorInterface $validator, ProductRepository $products, StockService $stock, #[CurrentUser] User $me): RedirectResponse
    {
        return $this->adjust(false, $request, $validator, $products, $stock, $me);
    }

    private function adjust(bool $add, Request $request, ValidatorInterface $validator, ProductRepository $products, StockService $stock, User $me): RedirectResponse
    {
        $token = $request->request->get('csrf_token');
        if (!$this->isCsrfTokenValid(self::CSRF_ID, is_string($token) ? $token : null)) {
            return $this->back('error', 'Your session expired. Please try again.');
        }

        $input = StockAdjustInput::fromRequest($request);
        if (count($validator->validate($input)) > 0) {
            return $this->back('error', self::INVALID);
        }

        $product = $products->find($input->productId);
        if (null === $product) {
            return $this->back('error', self::INVALID);
        }
        $this->denyAccessUnlessGranted(ProductVoter::STOCK, $product, 'You are not allowed to update stock.');

        try {
            if ($add) {
                $stock->add($input->productId, $input->size, $input->quantity, $me);

                return $this->back('success', sprintf('Added %d unit(s) of size %s to "%s".', $input->quantity, $input->size, $product->getName()));
            }

            $removed = $stock->reduce($input->productId, $input->size, $input->quantity, $me);

            return $this->back('success', sprintf('Removed %d unit(s) of size %s from "%s".', $removed, $input->size, $product->getName()));
        } catch (StoreException $e) {
            return $this->back('error', $e->getMessage());
        }
    }

    private function back(string $type, string $message): RedirectResponse
    {
        $this->addFlash($type, $message);

        return $this->redirectToRoute('app_staff_stock');
    }
}
