<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AccessToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
        parent::__construct($registry, AccessToken::class);
    }

    public function create(
        User $user,
        DateTimeImmutable $expiresAt = new DateTimeImmutable('+7 days')
    ): AccessToken {
        $accessToken = new AccessToken();
        $accessToken->setUser($user);
        $accessToken->setExpiresAt($expiresAt);

        $this->getEntityManager()->persist($accessToken);
        $this->getEntityManager()->flush();

        return $accessToken;
    }

    public function remove(?AccessToken $accessToken): void
    {
        if ($accessToken) {
            $this->getEntityManager()->remove($accessToken);
            $this->getEntityManager()->flush();
        }
    }

    public function removeByUser(User $user): void
    {
        $accessToken = $this->findOneByUser($user);
        $this->remove($accessToken);
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
