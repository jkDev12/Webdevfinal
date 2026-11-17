<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\ProductStock;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed', description: 'Create the admin account and the demo products (safe to run more than once)')]
final class SeedCommand extends Command
{
    private const PRODUCTS = [
        ['Gray Hoodie', 'images/gray-hoodie-mockup-front-back-260nw-2697869589.jpg', '1499.00', ['XS' => 5, 'S' => 8, 'M' => 10, 'L' => 6, 'XL' => 2]],
        ['Black Hoodie', 'images/360_F_686721990_oWvTbLdyUo7snaBjhEjinVUdIqQ7Airr.jpg', '1799.00', ['XS' => 3, 'S' => 6, 'M' => 12, 'L' => 8, 'XL' => 3]],
        ['Gray Tee', 'images/360_F_868101027_BufTMf4kyfeVYfPnSLRiOvD2hnhbFYOO.jpg', '699.00', ['XS' => 10, 'S' => 15, 'M' => 15, 'L' => 10, 'XL' => 5]],
        ['Washed Black Denim', 'images/30189775_59732655_600.webp', '1999.00', ['XS' => 0, 'S' => 4, 'M' => 6, 'L' => 4, 'XL' => 1]],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly ProductRepository $products,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('admin-username', null, InputOption::VALUE_REQUIRED, 'Admin username', 'admin')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Admin email', 'admin@example.com')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'Admin password', 'Admin12345')
            ->addOption('staff-password', null, InputOption::VALUE_REQUIRED, 'Demo staff password', 'Staff12345');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = (string) $input->getOption('admin-username');
        $email = (string) $input->getOption('admin-email');

        $admin = $this->users->findOneByUsernameOrEmail($username, $email);
        if (null === $admin) {
            $admin = (new User())->setUsername($username)->setEmail($email)->setRole(User::ROLE_ADMIN);
            $admin->setPassword($this->hasher->hashPassword($admin, (string) $input->getOption('admin-password')));
            $this->em->persist($admin);
            $io->success(sprintf('Admin "%s" created.', $username));
        } else {
            $io->note('Admin already exists, skipped.');
        }

        if (null === $this->users->findOneByUsernameOrEmail('staff', 'staff@example.com')) {
            $staff = (new User())->setUsername('staff')->setEmail('staff@example.com')->setRole(User::ROLE_STAFF);
            $staff->setPassword($this->hasher->hashPassword($staff, (string) $input->getOption('staff-password')));
            $this->em->persist($staff);
            $io->success('Demo staff "staff" created.');
        }

        if ([] === $this->products->findAll()) {
            foreach (self::PRODUCTS as [$name, $image, $price, $stock]) {
                $product = (new Product())->setName($name)->setImage($image)->setPrice($price)->setCreatedBy($admin);
                foreach (ProductStock::SIZES as $size) {
                    $product->addStock((new ProductStock())->setSize($size)->setQuantity($stock[$size]));
                }
                $this->em->persist($product);
            }
            $io->success(sprintf('%d demo products created.', count(self::PRODUCTS)));
        } else {
            $io->note('Products already exist, skipped.');
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
