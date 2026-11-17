<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\StoreTestCase;

final class AccountTest extends StoreTestCase
{
    public function testProfileShowsOwnDetails(): void
    {
        $this->createUser('sam', User::ROLE_STAFF);
        $this->logIn('sam');
        $this->client->request('GET', '/account');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.profile-list', 'sam@example.com');
        self::assertSelectorTextContains('.profile-list', 'staff');
    }

    public function testChangePassword(): void
    {
        $this->createUser('sam', User::ROLE_STAFF);
        $this->logIn('sam');

        $crawler = $this->client->request('GET', '/account/password');
        $this->client->submit($crawler->selectButton('Update password')->form([
            'change_password_form[currentPassword]' => 'wrong-one',
            'change_password_form[newPassword]' => 'Newpass12345',
            'change_password_form[confirmPassword]' => 'Newpass12345',
        ]));
        self::assertResponseStatusCodeSame(422);

        $crawler = $this->client->request('GET', '/account/password');
        $this->client->submit($crawler->selectButton('Update password')->form([
            'change_password_form[currentPassword]' => 'Passw0rdOK',
            'change_password_form[newPassword]' => 'Newpass12345',
            'change_password_form[confirmPassword]' => 'Newpass12345',
        ]));
        self::assertResponseRedirects('/account');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-success', 'password was changed');
        $this->client->request('GET', '/logout');

        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('LOG IN')->form(['username' => 'sam', 'password' => 'Newpass12345']));
        self::assertResponseRedirects();
    }
}
