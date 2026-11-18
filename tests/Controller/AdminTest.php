<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\ProductStock;
use App\Tests\StoreTestCase;

final class AdminTest extends StoreTestCase
{
    private function adminPage(): \Symfony\Component\DomCrawler\Crawler
    {
        return $this->client->request('GET', '/admin');
    }

    public function testCustomerCannotOpenAdmin(): void
    {
        $this->createUser('ana');
        $this->logIn('ana');
        $this->client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(403);
    }

    public function testDashboardShowsStockTableWithoutStockForms(): void
    {
        $this->createProduct('Gray Hoodie', ['M' => 2, 'S' => 7]);
        $this->createUser('boss', 'admin');
        $this->logIn('boss');
        $this->adminPage();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.admin-table', 'Gray Hoodie');
        self::assertSelectorExists('td.stock-low');
        self::assertSelectorExists('td.stock-out');
        self::assertSelectorNotExists('form.stock-form');
        self::assertSelectorTextContains('.stat-grid', 'Total users');
        self::assertSelectorTextContains('.stat-grid', 'Total staff');
        self::assertSelectorTextContains('.stat-grid', 'Total records');
    }

    public function testAddAndReduceStock(): void
    {
        $product = $this->createProduct('Gray Hoodie', ['M' => 2]);
        $this->createUser('boss', 'admin');
        $this->logIn('boss');

        $crawler = $this->client->request('GET', '/products/stock');
        $this->client->submit($crawler->selectButton('Add Stock')->form(['product_id' => $product->getId(), 'size' => 'M', 'quantity' => 10]));
        self::assertResponseRedirects('/products/stock');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-success', 'Added 10 unit(s) of size M');

        $crawler = $this->client->request('GET', '/products/stock');
        $this->client->submit($crawler->selectButton('Reduce Stock')->form(['product_id' => $product->getId(), 'size' => 'M', 'quantity' => 500]));
        $this->client->followRedirect();

        $this->em->clear();
        $qty = $this->em->getRepository(ProductStock::class)->findOneBy(['product' => $product->getId(), 'size' => 'M'])->getQuantity();
        self::assertSame(0, $qty, 'stock never goes below zero');
    }

    public function testMarkPaidAndCancelRestoresStock(): void
    {
        $product = $this->createProduct('Gray Hoodie', ['M' => 1]);
        $customer = $this->createUser('ana');
        $this->createUser('boss', 'admin');

        $this->logIn('ana');
        $this->client->request('GET', '/');
        $token = $this->client->getCrawler()->filter('meta[name=csrf-token]')->attr('content');
        $this->client->request('POST', '/checkout', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'product_id' => $product->getId(), 'size' => 'M', 'csrf_token' => $token,
            'shipping_name' => 'Ana', 'shipping_phone' => '1', 'shipping_address' => 'x', 'shipping_city' => 'y',
            'shipping_postal_code' => '1', 'payment_method' => 'cod', 'receipt_target' => 'account',
        ]));
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/logout');

        $this->logIn('boss');
        $crawler = $this->adminPage();
        self::assertSelectorTextContains('.payment-badge.pending', 'COD · Pending');

        $this->client->submit($crawler->selectButton('Mark Paid')->form());
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.payment-badge.paid', 'COD · Paid');

        $this->client->submit($crawler->selectButton('Cancel')->form());
        $this->client->followRedirect();
        self::assertSelectorTextContains('.status-badge.cancelled', 'Cancelled');

        $this->em->clear();
        $stock = $this->em->getRepository(ProductStock::class)->findOneBy(['product' => $product->getId(), 'size' => 'M']);
        self::assertSame(1, $stock->getQuantity(), 'cancelling gives the unit back');
        self::assertTrue($this->em->getRepository(Order::class)->findOneBy([])->isCancelled());
    }
}
