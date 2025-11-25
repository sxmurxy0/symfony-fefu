<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $repository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function create(string $phoneNumber, string $plainPassword): User
    {
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->setPhoneNumber($phoneNumber);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);

        return $user;
    }

    public function remove(?User $user): void
    {
        if ($user) {
            $this->em->remove($user);
        }
    }

    public function removeById(int $id): void
    {
        $user = $this->repository->find($id);
        $this->remove($user);
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }
}
