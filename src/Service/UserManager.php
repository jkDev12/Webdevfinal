<?php

namespace App\Service;

use App\Dto\UserData;
use App\Entity\User;
use App\Exception\StoreException;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepository $users,
        private readonly OrderRepository $orders,
    ) {
    }

    public function create(UserData $data): User
    {
        $this->assertUnique($data, null);

        $user = (new User())
            ->setUsername(trim((string) $data->username))
            ->setEmail(trim((string) $data->email))
            ->setRole((string) $data->role)
            ->setActive($data->active);
        $user->setPassword($this->hasher->hashPassword($user, (string) $data->password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function update(User $user, UserData $data, User $actor): void
    {
        $this->assertUnique($data, $user->getId());

        $user->setUsername(trim((string) $data->username))->setEmail(trim((string) $data->email));

        if ($user->getId() !== $actor->getId()) {
            $user->setRole((string) $data->role)->setActive($data->active);
        }

        if (null !== $data->password && '' !== $data->password) {
            $user->setPassword($this->hasher->hashPassword($user, $data->password));
        }

        $this->em->flush();
    }

    public function delete(User $user, User $actor): void
    {
        if (User::ROLE_ADMIN !== $actor->getRole()) {
            throw new StoreException('Only an administrator can delete accounts.');
}
        if ($user->getId() === $actor->getId()) {
            throw new StoreException('You cannot delete your own account.');
        }

        if ($this->orders->countForUser((int) $user->getId()) > 0) {
            throw new StoreException(sprintf('"%s" has placed orders, so the account cannot be deleted. Disable it instead.', $user->getUsername()));
        }

        $this->em->remove($user);
        $this->em->flush();
    }

    public function toggleActive(User $user, User $actor): bool
    {
        if ($user->getId() === $actor->getId()) {
            throw new StoreException('You cannot disable your own account.');
        }

        $user->setActive(!$user->isActive());
        $this->em->flush();

        return $user->isActive();
    }

    private function assertUnique(UserData $data, ?int $excludeId): void
    {
        if ($this->users->existsOther(trim((string) $data->username), trim((string) $data->email), $excludeId)) {
            throw new StoreException('That username or email is already registered.');
        }
    }
}
