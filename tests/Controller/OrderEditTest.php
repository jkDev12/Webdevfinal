<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Tests\StoreTestCase;

final class OrderEditTest extends StoreTestCase
{
    private function orderBy(User $buyer): Order
    {
        $order = (new Order())
            ->setUser($buyer)
            ->setProduct($this->createProduct('Gray Hoodie'))
            ->setSize('M')
            ->setQuantity(1)
            ->setShippingName('Old Name')
            ->setShippingPhone('09171234567')
            ->setShippingAddress('1 Old St')
            ->setShippingCity('Old City')
            ->setShippingPostalCode('1000')
            ->setReceiptEmail('old@example.com');
        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    private function newDetails(): array
    {
        return [
            'order_details_form[shippingName]' => 'New Name',
            'order_details_form[shippingPhone]' => '09998887777',
            'order_details_form[shippingAddress]' => '2 New Ave',
            'order_details_form[shippingCity]' => 'New City',
            'order_details_form[shippingPostalCode]' => '2000',
            'order_details_form[receiptEmail]' => 'new@example.com',
        ];
    }

    public function testAdminCanEditAnyOrderFromTheDashboard(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $order = $this->orderBy($this->createUser('ana'));
        $id = $order->getId();

        $this->logIn('boss');
        $this->client->request('GET', '/admin');
        self::assertSelectorExists('a[href$="/orders/'.$id.'/edit"]');

        $crawler = $this->client->request('GET', '/orders/'.$id.'/edit');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Save')->form($this->newDetails()));
        self::assertResponseRedirects('/admin');

        $this->em->clear();
        $saved = $this->em->find(Order::class, $id);
        self::assertSame(['New Name', '09998887777', '2 New Ave', 'New City', '2000', 'new@example.com'], [
            $saved->getShippingName(), $saved->getShippingPhone(), $saved->getShippingAddress(),
            $saved->getShippingCity(), $saved->getShippingPostalCode(), $saved->getReceiptEmail(),
        ]);
        self::assertSame('M', $saved->getSize(), 'only the details are editable');
    }

    public function testStaffCanEditOnlyOrdersTheyPlaced(): void
    {
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $mine = $this->orderBy($sam);
        $customers = $this->orderBy($this->createUser('ana'));
        $mineId = $mine->getId();
        $theirId = $customers->getId();

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/orders/'.$mineId.'/edit');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Save')->form($this->newDetails()));
        self::assertResponseRedirects('/account');

        $this->client->request('GET', '/orders/'.$theirId.'/edit');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/orders/'.$theirId.'/edit', ['order_details_form' => ['shippingName' => 'hacked']]);
        self::assertResponseStatusCodeSame(403);

        $this->em->clear();
        self::assertSame('Old Name', $this->em->find(Order::class, $theirId)->getShippingName());
    }

    public function testCustomerCannotEditOrders(): void
    {
        $ana = $this->createUser('ana');
        $id = $this->orderBy($ana)->getId();
        $this->logIn('ana');
        $this->client->request('GET', '/orders/'.$id.'/edit');
        self::assertResponseStatusCodeSame(403);
    }

    public function testInvalidDetailsAreRejected(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $id = $this->orderBy($this->createUser('ana'))->getId();

        $this->logIn('boss');
        $crawler = $this->client->request('GET', '/orders/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Save')->form(['order_details_form[shippingPhone]' => 'abc', 'order_details_form[receiptEmail]' => 'nope']));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.panel-field-error');

        $this->em->clear();
        self::assertSame('09171234567', $this->em->find(Order::class, $id)->getShippingPhone());
    }
}
