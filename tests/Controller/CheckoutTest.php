<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Tests\StoreTestCase;

final class CheckoutTest extends StoreTestCase
{
    private function payload(int $productId, string $csrf, array $override = []): string
    {
        return json_encode($override + [
            'product_id' => $productId, 'size' => 'M', 'csrf_token' => $csrf,
            'shipping_name' => 'Jane Doe', 'shipping_phone' => '09171234567',
            'shipping_address' => '1 Rizal St', 'shipping_city' => 'Manila', 'shipping_postal_code' => '1000',
            'payment_method' => 'cod', 'receipt_target' => 'account',
        ]);
    }

    private function post(string $body): array
    {
        $this->client->request('POST', '/checkout', server: ['CONTENT_TYPE' => 'application/json'], content: $body);

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private function storefrontToken(): string
    {
        $this->client->request('GET', '/');

        return $this->client->getCrawler()->filter('meta[name=csrf-token]')->attr('content');
    }

    public function testStorefrontListsProductsAndPassesThemToJs(): void
    {
        $product = $this->createProduct('Gray Hoodie', ['M' => 2]);
        $this->createUser('ana');
        $this->logIn('ana');
        $this->client->request('GET', '/');

        self::assertSelectorTextContains('#clothes'.$product->getId().' .product-title', 'Gray Hoodie');
        self::assertSelectorTextContains('#clothes'.$product->getId().' .product-price', '₱1,499.00');
        self::assertSelectorExists('.buy-button[data-product-id="'.$product->getId().'"]');
        self::assertStringContainsString('window.PRODUCTS', $this->client->getResponse()->getContent());
    }

    public function testAnonymousCheckoutGetsJson401(): void
    {
        $data = $this->post('{}');
        self::assertResponseStatusCodeSame(401);
        self::assertFalse($data['success']);
    }

    public function testCodOrderReservesStockAndIsPending(): void
    {
        $product = $this->createProduct('Gray Hoodie', ['M' => 2]);
        $this->createUser('ana');
        $this->logIn('ana');

        $data = $this->post($this->payload($product->getId(), $this->storefrontToken()));

        self::assertResponseIsSuccessful();
        self::assertTrue($data['success']);
        self::assertSame(1, $data['remaining']);
        self::assertStringContainsString('Pay in cash', $data['message']);

        $order = $this->em->getRepository(Order::class)->findOneBy([]);
        self::assertSame('pending', $order->getPaymentStatus());
        self::assertSame('ana@example.com', $order->getReceiptEmail());
    }

    public function testCardOrderIsPaidAndOnlyKeepsLastFourDigits(): void
    {
        $product = $this->createProduct('Gray Hoodie', ['M' => 1]);
        $this->createUser('ana');
        $this->logIn('ana');

        $data = $this->post($this->payload($product->getId(), $this->storefrontToken(), [
            'payment_method' => 'credit_card', 'card_name' => 'Jane Doe',
            'card_number' => '4242 4242 4242 4242', 'card_expiry' => '12/30',
        ]));

        self::assertTrue($data['success']);
        $order = $this->em->getRepository(Order::class)->findOneBy([]);
        self::assertSame('paid', $order->getPaymentStatus());
        self::assertSame('4242', $order->getCardLast4());
    }

    public function testSoldOutSizeIsRejectedWithConflict(): void
    {
        $product = $this->createProduct('Gray Hoodie', ['M' => 0]);
        $this->createUser('ana');
        $this->logIn('ana');

        $data = $this->post($this->payload($product->getId(), $this->storefrontToken()));

        self::assertResponseStatusCodeSame(409);
        self::assertSame('Sorry, that size just sold out.', $data['message']);
    }

    public function testValidationMessagesAreJoined(): void
    {
        $product = $this->createProduct();
        $this->createUser('ana');
        $this->logIn('ana');

        $data = $this->post($this->payload($product->getId(), $this->storefrontToken(), [
            'shipping_name' => '', 'shipping_city' => '', 'payment_method' => 'credit_card',
        ]));

        self::assertResponseStatusCodeSame(400);
        self::assertStringContainsString('Full name is required.', $data['message']);
        self::assertStringContainsString('City is required.', $data['message']);
        self::assertStringContainsString('Card number is required.', $data['message']);
    }

    public function testBadCsrfTokenIsRejected(): void
    {
        $product = $this->createProduct();
        $this->createUser('ana');
        $this->logIn('ana');

        $this->post($this->payload($product->getId(), 'wrong'));
        self::assertResponseStatusCodeSame(403);
    }

    public function testPhoneAndPostalCodeMustBeNumbers(): void
    {
        $product = $this->createProduct('Gray Hoodie', ['M' => 2]);
        $this->createUser('ana');
        $this->logIn('ana');

        $data = $this->post($this->payload($product->getId(), $this->storefrontToken(), ['shipping_phone' => '0917abc4567']));
        self::assertFalse($data['success']);
        self::assertStringContainsString('only contain numbers', $data['message']);

        $data = $this->post($this->payload($product->getId(), $this->storefrontToken(), ['shipping_postal_code' => '10A0']));
        self::assertFalse($data['success']);
        self::assertStringContainsString('only contain numbers', $data['message']);
    }
}
