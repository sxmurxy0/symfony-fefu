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

        $user->setPhoneNumber($phoneNumber);
        $user->setPlainPassword($plainPassword);
        $this->updatePassword($user);

        $this->em->persist($user);

        return $user;
    }

    public function remove(?User $user): void
    {
        if (null !== $user) {
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

    public function updatePassword(User $user): void
    {
        if (null === $user->getPlainPassword()) {
            return;
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());

        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }
}
