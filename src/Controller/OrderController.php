<?php

namespace App\Controller;

use App\Dto\OrderDetailsData;
use App\Entity\Order;
use App\Form\OrderDetailsFormType;
use App\Security\Voter\OrderVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Edit an order's shipping / receipt details. Admin: any order. Staff: only orders they placed. */
#[Route('/orders')]
#[IsGranted('ROLE_STAFF')]
final class OrderController extends AbstractController
{
    #[Route('/{id<\d+>}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    #[IsGranted(OrderVoter::EDIT, 'order', 'You are not allowed to edit this order.')]
    public function edit(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $data = OrderDetailsData::fromOrder($order);

        $form = $this->createForm(OrderDetailsFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->applyTo($order);
            $em->flush();

            $this->addFlash('success', sprintf('Order #%d updated.', $order->getId()));

            return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_admin_dashboard' : 'app_account');
        }

        return $this->render('orders/edit.html.twig', ['form' => $form, 'order' => $order]);
    }
}
