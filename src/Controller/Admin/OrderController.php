<?php

namespace App\Controller\Admin;

use App\Exception\StoreException;
use App\Service\OrderService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/order/{id<\d+>}')]
#[IsGranted('ROLE_ADMIN')]
final class OrderController extends AbstractAdminController
{
    #[Route('/paid', name: 'app_admin_order_paid', methods: ['POST'])]
    public function markPaid(int $id, Request $request, OrderService $orders): RedirectResponse
    {
        if (!$this->csrfIsValid($request)) {
            return $this->expired();
        }

        $orders->markPaid($id);

        return $this->backToDashboard('success', "Order #{$id} marked as paid.");
    }

    #[Route('/cancel', name: 'app_admin_order_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request, OrderService $orders): RedirectResponse
    {
        if (!$this->csrfIsValid($request)) {
            return $this->expired();
        }

        try {
            $orders->cancel($id);
        } catch (StoreException $e) {
            return $this->backToDashboard('error', $e->getMessage());
        }

        return $this->backToDashboard('success', "Order #{$id} was cancelled and its stock restored.");
    }
}
