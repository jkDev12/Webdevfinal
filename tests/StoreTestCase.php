<?php

namespace App\Tests;

use App\Entity\Product;
use App\Entity\ProductStock;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class StoreTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $tool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    protected function createUser(string $username, string $role = User::ROLE_CUSTOMER): User
    {
        $user = (new User())->setUsername($username)->setEmail($username.'@example.com')->setRole($role);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, 'Passw0rdOK'));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createProduct(string $name = 'Gray Hoodie', array $stock = ['M' => 2]): Product
    {
        $product = (new Product())->setName($name)->setImage('images/logo.png')->setPrice('1499.00');
        foreach (ProductStock::SIZES as $size) {
            $product->addStock((new ProductStock())->setSize($size)->setQuantity($stock[$size] ?? 0));
        }
        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    protected function logIn(string $username): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('LOG IN')->form(['username' => $username, 'password' => 'Passw0rdOK']));
    }
}
