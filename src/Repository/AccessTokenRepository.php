<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AccessToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
        parent::__construct($registry, AccessToken::class);
    }

    public function findOneByValue(string $value): ?AccessToken
    {
        return $this->findOneBy(['value' => $value]);
    }

    public function findOneByUser(User $user): ?AccessToken
    {
        return $this->findOneBy(['user' => $user]);
    }
}
