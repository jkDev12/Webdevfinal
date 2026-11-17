<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Tests\StoreTestCase;

final class ProductAccessTest extends StoreTestCase
{
    public function testCustomerCannotOpenProducts(): void
    {
        $this->createUser('ana');
        $this->logIn('ana');
        $this->client->request('GET', '/products');
        self::assertResponseStatusCodeSame(403);
    }

    public function testStaffCreatesProductAndOwnsIt(): void
    {
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $this->logIn('sam');

        $crawler = $this->client->request('GET', '/products/new');
        $form = $crawler->selectButton('Save')->form([
            'product_form[name]' => 'Red Cap', 'product_form[price]' => '499',
            'product_form[stock][M]' => 5,
        ]);
        $form['product_form[image]']->upload(dirname(__DIR__).'/fixtures/photo.png');
        $this->client->submit($form);
        self::assertResponseRedirects('/products');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-success', 'Red Cap');

        $product = $this->em->getRepository(Product::class)->findOneBy(['name' => 'Red Cap']);
        self::assertSame($sam->getId(), $product->getCreatedBy()->getId());
    }

    public function testStaffCannotEditOrDeleteOthersRecords(): void
    {
        $boss = $this->createUser('boss', User::ROLE_ADMIN);
        $this->createUser('sam', User::ROLE_STAFF);
        $product = $this->createProduct('Gray Hoodie');
        $product->setCreatedBy($boss);
        $this->em->flush();
        $id = $product->getId();

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/products');
        self::assertSelectorTextContains('.admin-table', 'Gray Hoodie');
        self::assertSelectorNotExists('a[href$="/'.$id.'/edit"]');
        self::assertSelectorNotExists('form[action$="/'.$id.'/delete"]');

        $this->client->request('GET', '/products/'.$id.'/edit');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/products/'.$id.'/delete');
        self::assertResponseStatusCodeSame(403);
    }

    public function testStaffDeletesOwnAndAdminDeletesAny(): void
    {
        $boss = $this->createUser('boss', User::ROLE_ADMIN);
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $mine = $this->createProduct('Mine');
        $mine->setCreatedBy($sam);
        $theirs = $this->createProduct('Theirs');
        $theirs->setCreatedBy($sam);
        $this->em->flush();

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/products');
        $this->client->submit($crawler->filter('form[action$="/'.$mine->getId().'/delete"]')->form());
        $this->client->followRedirect();
        self::assertSelectorTextNotContains('.admin-table', 'Mine');
        $this->client->request('GET', '/logout');

        $this->logIn('boss');
        $crawler = $this->client->request('GET', '/products');
        $this->client->submit($crawler->filter('form[action$="/'.$theirs->getId().'/delete"]')->form());
        $this->client->followRedirect();
        $this->em->clear();
        self::assertNull($this->em->getRepository(Product::class)->findOneBy(['name' => 'Theirs']));
    }

    public function testProductSearchAndMineFilter(): void
    {
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $a = $this->createProduct('Blue Tee');
        $a->setCreatedBy($sam);
        $this->createProduct('Green Hoodie');
        $this->em->flush();

        $this->logIn('sam');
        $this->client->request('GET', '/products?q=hoodie');
        self::assertSelectorTextContains('.admin-table', 'Green Hoodie');
        self::assertSelectorTextNotContains('.admin-table', 'Blue Tee');

        $this->client->request('GET', '/products?creator=mine');
        self::assertSelectorTextContains('.admin-table', 'Blue Tee');
        self::assertSelectorTextNotContains('.admin-table', 'Green Hoodie');
    }
}
