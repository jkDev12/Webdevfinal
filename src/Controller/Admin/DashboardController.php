<?php

namespace App\Controller\Admin;

use App\Entity\ProductStock;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractAdminController
{
    #[Route('/admin', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(ProductRepository $products, OrderRepository $orders, UserRepository $users): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'users' => $users->countAll(),
                'staff' => $users->countByRole(User::ROLE_STAFF),
                'records' => $products->countAll(),
                'orders' => $orders->countAll(),
            ],
            'products' => $products->findAllWithStock(),
            'sizes' => ProductStock::SIZES,
            'orders' => $orders->findRecent(20),
        ]);
    }
}
