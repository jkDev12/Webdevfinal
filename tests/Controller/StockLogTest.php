<?php

namespace App\Tests\Controller;

use App\Entity\ProductStock;
use App\Entity\StockMovement;
use App\Entity\User;
use App\Tests\StoreTestCase;

final class StockLogTest extends StoreTestCase
{
    private function postStock(string $path, int $productId, string $size, int $qty): void
    {
        $crawler = $this->client->request('GET', '/products/stock');
        $token = $crawler->filter('form[action$="'.$path.'"] input[name=csrf_token]')->attr('value');
        $this->client->request('POST', '/products/stock/'.$path, [
            'csrf_token' => $token, 'product_id' => $productId, 'size' => $size, 'quantity' => $qty,
        ]);
    }

    private function qty(int $productId, string $size): int
    {
        $this->em->clear();

        return $this->em->getRepository(ProductStock::class)->findOneBy(['product' => $productId, 'size' => $size])->getQuantity();
    }

    public function testStaffCanPostAndRemoveStockOnAnyProductAndItIsLogged(): void
    {
        $boss = $this->createUser('boss', User::ROLE_ADMIN);
        $this->createUser('sam', User::ROLE_STAFF);
        $product = $this->createProduct('Gray Hoodie', ['M' => 5]);
        $product->setCreatedBy($boss);
        $this->em->flush();
        $id = $product->getId();

        $this->logIn('sam');
        $this->postStock('add', $id, 'M', 10);
        self::assertResponseRedirects('/products/stock');
        self::assertSame(15, $this->qty($id, 'M'));

        $this->postStock('reduce', $id, 'M', 100);
        self::assertSame(0, $this->qty($id, 'M'));

        $rows = $this->em->getRepository(StockMovement::class)->findBy([], ['id' => 'ASC']);
        self::assertCount(2, $rows);
        self::assertSame([StockMovement::TYPE_ADD, 10, 5, 15, 'sam'], [$rows[0]->getType(), $rows[0]->getQuantity(), $rows[0]->getQuantityBefore(), $rows[0]->getQuantityAfter(), $rows[0]->getActorName()]);
        self::assertSame([StockMovement::TYPE_REMOVE, 15, 0], [$rows[1]->getType(), $rows[1]->getQuantity(), $rows[1]->getQuantityAfter()]);
    }

    public function testStaffCanReadTheLogButCustomerAndAnonymousCannot(): void
    {
        $this->createUser('sam', User::ROLE_STAFF);
        $this->createUser('ana');

        $this->client->request('GET', '/logs');
        self::assertResponseRedirects('/login');

        $this->logIn('ana');
        $this->client->request('GET', '/logs');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/logout');

        $this->logIn('sam');
        $this->client->request('GET', '/logs');
        self::assertResponseIsSuccessful();
    }

    public function testCustomerCannotUpdateStock(): void
    {
        $this->createUser('ana');
        $product = $this->createProduct();
        $this->logIn('ana');
        $this->client->request('POST', '/products/stock/add', ['product_id' => $product->getId(), 'size' => 'M', 'quantity' => 5]);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(2, $this->qty($product->getId(), 'M'));
    }

    public function testStaffStockUpdateRejectsBadCsrf(): void
    {
        $this->createUser('sam', User::ROLE_STAFF);
        $product = $this->createProduct();
        $this->logIn('sam');
        $this->client->request('POST', '/products/stock/add', ['csrf_token' => 'nope', 'product_id' => $product->getId(), 'size' => 'M', 'quantity' => 5]);
        self::assertSame(2, $this->qty($product->getId(), 'M'));
        self::assertCount(0, $this->em->getRepository(StockMovement::class)->findAll());
    }

    public function testAdminCanSearchAndFilterTheLog(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $this->createUser('sam', User::ROLE_STAFF);
        $hoodie = $this->createProduct('Gray Hoodie', ['M' => 5, 'L' => 5]);
        $tee = $this->createProduct('Black Tee', ['M' => 5]);

        $this->logIn('sam');
        $this->postStock('add', $hoodie->getId(), 'M', 3);
        $this->postStock('reduce', $tee->getId(), 'M', 1);
        $this->postStock('add', $hoodie->getId(), 'L', 2);
        $this->client->request('GET', '/logout');

        $this->logIn('boss');
        $this->client->request('GET', '/logs');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(3, 'tbody tr');

        $this->client->request('GET', '/logs?q=tee');
        self::assertSelectorCount(1, 'tbody tr');
        self::assertSelectorTextContains('tbody', 'Black Tee');

        $this->client->request('GET', '/logs?type=add');
        self::assertSelectorCount(2, 'tbody tr');

        $this->client->request('GET', '/logs?type=add&size=L');
        self::assertSelectorCount(1, 'tbody tr');

        $this->client->request('GET', '/logs?q=sam&from='.date('Y-m-d').'&to='.date('Y-m-d'));
        self::assertSelectorCount(3, 'tbody tr');

        $this->client->request('GET', '/logs?from=2000-01-01&to=2000-01-02');
        self::assertSelectorTextContains('tbody', 'No stock records match.');
    }

    public function testAdminStockChangesAreLoggedAndOldDashboardRouteIsGone(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $product = $this->createProduct('Gray Hoodie', ['M' => 5]);
        $this->logIn('boss');

        $this->postStock('add', $product->getId(), 'M', 4);

        $this->client->request('POST', '/admin/stock/add', ['product_id' => $product->getId(), 'size' => 'M', 'quantity' => 4]);
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/logs');
        self::assertSelectorTextContains('tbody', 'boss');
        self::assertSelectorTextContains('tbody', '5 → 9');
    }

    public function testEditingStockInProductFormIsLogged(): void
    {
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $product = $this->createProduct('Gray Hoodie', ['M' => 5]);
        $product->setCreatedBy($sam);
        $this->em->flush();

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/products/'.$product->getId().'/edit');
        $this->client->submit($crawler->selectButton('Save')->form(['product_form[stock][M]' => 2]));

        $rows = $this->em->getRepository(StockMovement::class)->findAll();
        self::assertCount(1, $rows);
        self::assertSame([StockMovement::TYPE_REMOVE, 3, StockMovement::SOURCE_PRODUCT_EDIT], [$rows[0]->getType(), $rows[0]->getQuantity(), $rows[0]->getSource()]);
    }

    private function movementBy(User $actor, string $name = 'Gray Hoodie'): StockMovement
    {
        $product = $this->createProduct($name, ['M' => 5]);
        $movement = StockMovement::record($product, 'M', 5, 10, $actor, StockMovement::SOURCE_ADJUSTMENT);
        $this->em->persist($movement);
        $this->em->flush();

        return $movement;
    }

    public function testStaffCanEditOnlyTheirOwnLogEntries(): void
    {
        $boss = $this->createUser('boss', User::ROLE_ADMIN);
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $kim = $this->createUser('kim', User::ROLE_STAFF);
        $mine = $this->movementBy($sam, 'Mine');
        $kims = $this->movementBy($kim, 'Kims');
        $admins = $this->movementBy($boss, 'Admins');

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/logs');
        self::assertSelectorCount(3, 'tbody tr');
        self::assertSelectorExists('a[href$="/logs/'.$mine->getId().'/edit"]');
        self::assertSelectorNotExists('a[href$="/logs/'.$kims->getId().'/edit"]');
        self::assertSelectorNotExists('a[href$="/logs/'.$admins->getId().'/edit"]');

        $crawler = $this->client->request('GET', '/logs/'.$mine->getId().'/edit');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Save')->form(['log_entry_form[note]' => 'Recount done']));
        self::assertResponseRedirects('/logs');
        $this->em->clear();
        self::assertSame('Recount done', $this->em->find(StockMovement::class, $mine->getId())->getNote());

        foreach ([$kims, $admins] as $other) {
            $this->client->request('GET', '/logs/'.$other->getId().'/edit');
            self::assertResponseStatusCodeSame(403);
            $this->client->request('POST', '/logs/'.$other->getId().'/edit', ['log_entry_form' => ['note' => 'hacked']]);
            self::assertResponseStatusCodeSame(403);
        }
        $this->em->clear();
        self::assertNull($this->em->find(StockMovement::class, $kims->getId())->getNote());
    }

    public function testAdminCanEditEveryEntryIncludingStaffEntries(): void
    {
        $boss = $this->createUser('boss', User::ROLE_ADMIN);
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $staffEntry = $this->movementBy($sam, 'Sams');
        $adminEntry = $this->movementBy($boss, 'Bosses');

        $this->logIn('boss');
        $this->client->request('GET', '/logs');
        self::assertSelectorExists('a[href$="/logs/'.$adminEntry->getId().'/edit"]');
        self::assertSelectorExists('a[href$="/logs/'.$staffEntry->getId().'/edit"]');

        foreach ([$adminEntry->getId() => 'Admin note', $staffEntry->getId() => 'Checked by admin'] as $id => $note) {
            $crawler = $this->client->request('GET', '/logs/'.$id.'/edit');
            self::assertResponseIsSuccessful();
            $this->client->submit($crawler->selectButton('Save')->form(['log_entry_form[note]' => $note]));
            self::assertResponseRedirects('/logs');
            $this->em->clear();
            self::assertSame($note, $this->em->find(StockMovement::class, $id)->getNote());
        }
    }

    public function testDashboardNoLongerHasStockForms(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $this->logIn('boss');
        $this->client->request('GET', '/admin');
        self::assertSelectorNotExists('form[action$="/admin/stock/add"]');
        self::assertSelectorNotExists('form[action$="/admin/stock/reduce"]');
    }

    private function deleteLog(int $id): void
    {
        $crawler = $this->client->request('GET', '/logs');
        $form = $crawler->filter('form[action$="/logs/'.$id.'/delete"]');
        if (0 === $form->count()) {
            $this->client->request('POST', '/logs/'.$id.'/delete');

            return;
        }
        $this->client->submit($form->form());
    }

    public function testStaffCanDeleteOnlyTheirOwnLogEntries(): void
    {
        $boss = $this->createUser('boss', User::ROLE_ADMIN);
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $kim = $this->createUser('kim', User::ROLE_STAFF);
        $mine = $this->movementBy($sam, 'Mine');
        $kims = $this->movementBy($kim, 'Kims');
        $admins = $this->movementBy($boss, 'Admins');
        [$mineId, $kimsId, $adminsId] = [$mine->getId(), $kims->getId(), $admins->getId()];

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/logs');
        self::assertSelectorNotExists('form[action$="/logs/'.$kimsId.'/delete"]');
        self::assertSelectorNotExists('form[action$="/logs/'.$adminsId.'/delete"]');

        $this->client->request('POST', '/logs/'.$kimsId.'/delete');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/logs/'.$adminsId.'/delete');
        self::assertResponseStatusCodeSame(403);

        $this->deleteLog($mineId);
        self::assertResponseRedirects('/logs');
        $this->em->clear();
        self::assertNull($this->em->find(StockMovement::class, $mineId));
        self::assertNotNull($this->em->find(StockMovement::class, $kimsId));
        self::assertNotNull($this->em->find(StockMovement::class, $adminsId));
    }

    public function testAdminCanDeleteAnyLogEntryAndStockIsUntouched(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $entry = $this->movementBy($sam, 'Sams');
        $productId = $entry->getProduct()->getId();
        $id = $entry->getId();

        $this->logIn('boss');
        $this->deleteLog($id);
        self::assertResponseRedirects('/logs');
        $this->em->clear();
        self::assertNull($this->em->find(StockMovement::class, $id));
        self::assertSame(5, $this->qty($productId, 'M'));
    }

    public function testDeleteRejectsBadCsrf(): void
    {
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $id = $this->movementBy($sam)->getId();

        $this->logIn('sam');
        $this->client->request('POST', '/logs/'.$id.'/delete', ['csrf_token' => 'nope']);
        self::assertResponseRedirects('/logs');
        $this->em->clear();
        self::assertNotNull($this->em->find(StockMovement::class, $id));
    }

    public function testOwnerCanEditAllDetailsOfALogEntryWithoutTouchingStock(): void
    {
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $entry = $this->movementBy($sam, 'Mine'); // +5: 5 -> 10, size M
        $id = $entry->getId();
        $productId = $entry->getProduct()->getId();

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/logs/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Save')->form([
            'log_entry_form[type]' => 'remove',
            'log_entry_form[size]' => 'L',
            'log_entry_form[quantity]' => 3,
            'log_entry_form[note]' => 'Damaged in transit',
        ]));
        self::assertResponseRedirects('/logs');

        $this->em->clear();
        $m = $this->em->find(StockMovement::class, $id);
        self::assertSame([StockMovement::TYPE_REMOVE, 'L', 3, 5, 2, 'Damaged in transit'], [$m->getType(), $m->getSize(), $m->getQuantity(), $m->getQuantityBefore(), $m->getQuantityAfter(), $m->getNote()]);
        self::assertSame(5, $this->qty($productId, 'M'), 'real stock is untouched');
    }

    public function testLogEntryEditRejectsInvalidValues(): void
    {
        $sam = $this->createUser('sam', User::ROLE_STAFF);
        $id = $this->movementBy($sam)->getId(); // before = 5

        $this->logIn('sam');
        $crawler = $this->client->request('GET', '/logs/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Save')->form(['log_entry_form[type]' => 'remove', 'log_entry_form[quantity]' => 99]));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.panel-field-error', 'cannot remove more than');

        $crawler = $this->client->request('GET', '/logs/'.$id.'/edit');
        $this->client->submit($crawler->selectButton('Save')->form(['log_entry_form[quantity]' => 0]));
        self::assertResponseStatusCodeSame(422);

        $this->em->clear();
        self::assertSame(5, $this->em->find(StockMovement::class, $id)->getQuantity());
    }
}
