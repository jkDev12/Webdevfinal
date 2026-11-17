<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneByUsernameOrEmail($identifier, $identifier);
    }

    public function findOneByUsernameOrEmail(string $username, string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsOther(string $username, string $email, ?int $excludeId): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $email);

        if (null !== $excludeId) {
            $qb->andWhere('u.id <> :id')->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function search(?string $query = null, ?string $role = null): array
    {
        $qb = $this->createQueryBuilder('u')->orderBy('u.id', 'DESC');

        if (null !== $query && '' !== $query) {
            $qb->andWhere('LOWER(u.username) LIKE :q OR LOWER(u.email) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($query).'%');
        }
        if (null !== $role && in_array($role, User::ROLES, true)) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function countByRole(string $role): int
    {
        return $this->count(['role' => $role]);
    }

    public function findStaffAndAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role IN (:roles)')
            ->setParameter('roles', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
