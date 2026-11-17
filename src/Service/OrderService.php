<?php

namespace App\Service;

use App\Dto\CheckoutInput;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\SoldOutException;
use App\Exception\StoreException;
use App\Repository\OrderRepository;
use App\Repository\ProductStockRepository;
use Doctrine\ORM\EntityManagerInterface;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderRepository $orders,
        private readonly ProductStockRepository $stocks,
    ) {
    }

    public function place(User $user, int $productId, string $size, CheckoutInput $checkout): int
    {
        return $this->em->wrapInTransaction(function () use ($user, $productId, $size, $checkout): int {
            if (!$this->stocks->reserveOne($productId, $size)) {
                throw new SoldOutException('Sorry, that size just sold out.');
            }

            $order = (new Order())
                ->setUser($user)
                ->setProduct($this->em->getReference(Product::class, $productId))
                ->setSize($size)
                ->setQuantity(1)
                ->setPaymentMethod($checkout->paymentMethod)
                ->setPaymentStatus($checkout->isCard() ? Order::PAYMENT_STATUS_PAID : Order::PAYMENT_STATUS_PENDING)
                ->setCardName($checkout->isCard() ? $checkout->cardName : null)
                ->setCardLast4($checkout->cardLast4())
                ->setShippingName($checkout->shippingName)
                ->setShippingPhone($checkout->shippingPhone)
                ->setShippingAddress($checkout->shippingAddress)
                ->setShippingCity($checkout->shippingCity)
                ->setShippingPostalCode($checkout->shippingPostalCode)
                ->setReceiptEmail($checkout->receiptEmailFor($user->getEmail()));

            $this->em->persist($order);
            $this->em->flush();

            return $this->stocks->quantityFor($productId, $size);
        });
    }

    public function markPaid(int $orderId): void
    {
        $this->orders->markCodPaid($orderId);
    }

    public function cancel(int $orderId): void
    {
        $order = $this->orders->find($orderId) ?? throw new StoreException('That order could not be found.');

        if ($order->isCancelled()) {
            throw new StoreException("Order #{$orderId} is already cancelled.");
        }

        $productId = (int) $order->getProduct()?->getId();
        $size = $order->getSize();
        $quantity = $order->getQuantity();

        $this->em->wrapInTransaction(function () use ($orderId, $productId, $size, $quantity): void {
            if (1 === $this->orders->cancelIfActive($orderId)) {

                $this->stocks->increase($productId, $size, $quantity);
            }
        });
    }
}
