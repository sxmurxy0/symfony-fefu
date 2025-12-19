<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class AccessTokenService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AccessTokenRepository $repository
    ) {
    }

    public function create(
        User $user,
        DateTimeImmutable $expiresAt = new DateTimeImmutable('+7 days')
    ): AccessToken {
        $accessToken = new AccessToken();
        $accessToken->setUser($user);
        $accessToken->setExpiresAt($expiresAt);

        $this->em->persist($accessToken);

        return $accessToken;
    }

    public function remove(?AccessToken $accessToken): void
    {
        if ($accessToken) {
            $this->em->remove($accessToken);
        }
    }

    public function removeByUser(User $user): void
    {
        $accessToken = $this->repository->findOneByUser($user);
        $this->remove($accessToken);
    }
}
