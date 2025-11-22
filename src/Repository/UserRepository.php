<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct($registry, User::class);
    }

    public function create(string $phoneNumber, string $password): User
    {
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);

        $user->setPhoneNumber($phoneNumber);
        $user->setPassword($hashedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    public function remove(?User $user): void
    {
        if ($user) {
            $this->getEntityManager()->remove($user);
            $this->getEntityManager()->flush();
        }
    }

    public function removeById(int $id): void
    {
        $user = $this->find($id);
        $this->remove($user);
    }

    public function findOneByPhoneNumber(string $phoneNumber): ?User
    {
        return $this->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    public function existsWithPhoneNumber(string $phoneNumber): bool
    {
        return null != $this->findOneByPhoneNumber($phoneNumber);
    }
}
