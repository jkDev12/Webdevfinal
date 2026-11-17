<?php

namespace App\Tests\Controller;

use App\Tests\StoreTestCase;

final class AuthTest extends StoreTestCase
{
    public function testAnonymousVisitorIsSentToLogin(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/login');
    }

    public function testRegisterLogsInAndShowsStorefront(): void
    {
        $crawler = $this->client->request('GET', '/register');
        self::assertSelectorExists('form.auth-form');

        $this->client->submit($crawler->selectButton('CREATE ACCOUNT')->form([
            'username' => 'newbie', 'email' => 'newbie@example.com',
            'password' => 'abcd1234', 'confirm_password' => 'abcd1234',
        ]));
        self::assertResponseRedirects('/');
        $this->client->followRedirect();

        self::assertSelectorTextContains('.welcome-text', 'Hi, newbie');
        self::assertSelectorTextContains('#flashToast', 'Welcome, newbie!');
    }

    public function testRegisterShowsAllValidationErrorsInOneLine(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->client->submit($crawler->selectButton('CREATE ACCOUNT')->form([
            'username' => 'x', 'email' => 'nope', 'password' => 'short', 'confirm_password' => 'different',
        ]));

        self::assertSelectorTextContains('.auth-error', 'Username must be 3-30 characters');
        self::assertSelectorTextContains('.auth-error', 'Enter a valid email address.');
        self::assertSelectorTextContains('.auth-error', 'Password must be at least 8 characters long.');
        self::assertSelectorTextContains('.auth-error', 'Passwords do not match.');
    }

    public function testRegisterRejectsDuplicateUsername(): void
    {
        $this->createUser('taken');
        $crawler = $this->client->request('GET', '/register');
        $this->client->submit($crawler->selectButton('CREATE ACCOUNT')->form([
            'username' => 'taken', 'email' => 'other@example.com',
            'password' => 'abcd1234', 'confirm_password' => 'abcd1234',
        ]));

        self::assertSelectorTextContains('.auth-error', 'That username or email is already registered.');
    }

    public function testLoginByUsernameAndByEmail(): void
    {
        $this->createUser('ana');
        $this->logIn('ana');
        self::assertResponseRedirects('/');
        $this->client->request('GET', '/logout');

        $this->logIn('ana@example.com');
        self::assertResponseRedirects('/');
    }

    public function testBadPasswordShowsGenericError(): void
    {
        $this->createUser('ana');
        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('LOG IN')->form(['username' => 'ana', 'password' => 'wrong']));
        $this->client->followRedirect();

        self::assertSelectorTextContains('.auth-error', 'Invalid username or password.');
    }

    public function testAdminLandsOnDashboard(): void
    {
        $this->createUser('boss', 'admin');
        $this->logIn('boss');
        self::assertResponseRedirects('/admin');
    }

    public function testStaffRegistrationWaitsForAdminApproval(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->client->submit($crawler->selectButton('CREATE ACCOUNT')->form([
            'username' => 'newstaff', 'email' => 'newstaff@example.com', 'account_type' => 'staff',
            'password' => 'Abcd12345', 'confirm_password' => 'Abcd12345',
        ]));
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-success', 'administrator must approve');

        $user = $this->em->getRepository(\App\Entity\User::class)->findOneBy(['username' => 'newstaff']);
        self::assertSame('staff', $user->getRole());
        self::assertFalse($user->isActive());

        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('LOG IN')->form(['username' => 'newstaff', 'password' => 'Abcd12345']));
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'disabled');
    }
}
