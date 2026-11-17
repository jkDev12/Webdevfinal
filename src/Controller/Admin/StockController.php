<?php

namespace App\Controller\Admin;

use App\Dto\StockAdjustInput;
use App\Service\StockService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/stock')]
#[IsGranted('ROLE_ADMIN')]
final class StockController extends AbstractAdminController
{
    #[Route('/add', name: 'app_admin_stock_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator, StockService $stock): RedirectResponse
    {
        if (!$this->csrfIsValid($request)) {
            return $this->expired();
        }

        $input = StockAdjustInput::fromRequest($request);
        if (count($validator->validate($input)) > 0) {
            return $this->backToDashboard('error', 'Please choose a product, a size, and a positive quantity.');
        }

        $stock->add($input->productId, $input->size, $input->quantity);

        return $this->backToDashboard('success', "Added {$input->quantity} unit(s) of size {$input->size}.");
    }

    #[Route('/reduce', name: 'app_admin_stock_reduce', methods: ['POST'])]
    public function reduce(Request $request, ValidatorInterface $validator, StockService $stock): RedirectResponse
    {
        if (!$this->csrfIsValid($request)) {
            return $this->expired();
        }

        $input = StockAdjustInput::fromRequest($request);
        if (count($validator->validate($input)) > 0) {
            return $this->backToDashboard('error', 'Please choose a product, a size, and a positive quantity.');
        }

        $stock->reduce($input->productId, $input->size, $input->quantity);

        return $this->backToDashboard('success', "Removed {$input->quantity} unit(s) of size {$input->size}.");
    }
}
