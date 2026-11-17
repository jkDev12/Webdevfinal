<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\StoreTestCase;

final class UserManagementTest extends StoreTestCase
{
    public function testStaffAndCustomerCannotReachAdminArea(): void
    {
        $this->createUser('sam', User::ROLE_STAFF);
        $this->logIn('sam');
        foreach (['/admin', '/admin/users', '/admin/users/new'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }

    public function testAnonymousIsSentToLogin(): void
    {
        $this->client->request('GET', '/admin/users');
        self::assertResponseRedirects('/login');
        $this->client->request('GET', '/products');
        self::assertResponseRedirects('/login');
    }

    public function testAdminCreatesStaffWhoCanLogIn(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $this->logIn('boss');

        $crawler = $this->client->request('GET', '/admin/users/new');
        $this->client->submit($crawler->selectButton('Save')->form([
            'user_form[username]' => 'clerk1', 'user_form[email]' => 'clerk1@example.com',
            'user_form[role]' => 'staff', 'user_form[password]' => 'Abcd12345',
        ]));
        self::assertResponseRedirects('/admin/users');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-success', 'clerk1');
        self::assertSelectorTextContains('.admin-table', 'clerk1@example.com');

        $stored = $this->em->getRepository(User::class)->findOneBy(['username' => 'clerk1']);
        self::assertNotSame('Abcd12345', $stored->getPassword(), 'password is hashed');
        $this->client->request('GET', '/logout');

        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('LOG IN')->form(['username' => 'clerk1', 'password' => 'Abcd12345']));
        $this->client->followRedirect();
        $this->client->request('GET', '/products');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(403);
    }

    public function testDuplicateAndWeakPasswordRejected(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $this->logIn('boss');

        $crawler = $this->client->request('GET', '/admin/users/new');
        $this->client->submit($crawler->selectButton('Save')->form([
            'user_form[username]' => 'boss', 'user_form[email]' => 'x@example.com',
            'user_form[role]' => 'staff', 'user_form[password]' => 'short',
        ]));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.panel-field-error');
    }

    public function testDisabledAccountCannotLogIn(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $ana = $this->createUser('ana');
        $this->logIn('boss');

        $crawler = $this->client->request('GET', '/admin/users');
        $this->client->submit($crawler->filter('form[action$="/'.$ana->getId().'/toggle"]')->form());
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-success', 'disabled');
        $this->client->request('GET', '/logout');

        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('LOG IN')->form(['username' => 'ana', 'password' => 'Passw0rdOK']));
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'disabled');
    }

    public function testAdminDeletesUserButNotSelf(): void
    {
        $boss = $this->createUser('boss', User::ROLE_ADMIN);
        $ana = $this->createUser('ana');
        $this->logIn('boss');

        $crawler = $this->client->request('GET', '/admin/users');
        self::assertSelectorNotExists('form[action$="/'.$boss->getId().'/delete"]');
        $this->client->submit($crawler->filter('form[action$="/'.$ana->getId().'/delete"]')->form());
        $this->client->followRedirect();
        $this->em->clear();
        self::assertNull($this->em->getRepository(User::class)->findOneBy(['username' => 'ana']));
    }

    public function testSearchAndRoleFilter(): void
    {
        $this->createUser('boss', User::ROLE_ADMIN);
        $this->createUser('sam', User::ROLE_STAFF);
        $this->createUser('ana');
        $this->logIn('boss');

        $this->client->request('GET', '/admin/users?role=staff');
        self::assertSelectorTextContains('.admin-table', 'sam');
        self::assertSelectorTextNotContains('.admin-table', 'ana@example.com');

        $this->client->request('GET', '/admin/users?q=ana');
        self::assertSelectorTextContains('.admin-table', 'ana');
        self::assertSelectorTextNotContains('.admin-table', 'sam@example.com');
    }
}
