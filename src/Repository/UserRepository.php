<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
        parent::__construct($registry, User::class);
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
