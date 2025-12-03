<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use App\Service\AccessTokenService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AccessTokenServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private AccessTokenRepository $repository;
    private AccessTokenService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(AccessTokenRepository::class);
        $this->service = new AccessTokenService($this->entityManager, $this->repository);
    }

    public function testCreate(): void
    {
        $user = new User();
        $user->setPhoneNumber('+12345617890');
        $user->setPassword('password');

        $expiresAt = new DateTimeImmutable('+7 days');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AccessToken::class));

        $accessToken = $this->service->create($user, $expiresAt);

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertSame($user, $accessToken->getUser());
        $this->assertSame($expiresAt, $accessToken->getExpiresAt());
    }

    public function testCreateWithDefaultExpiresAt(): void
    {
        $user = new User();
        $user->setPhoneNumber('+12345678290');
        $user->setPassword('password');

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $accessToken = $this->service->create($user);

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertSame($user, $accessToken->getUser());
        $this->assertNotNull($accessToken->getExpiresAt());
    }

    public function testRemoveWithToken(): void
    {
        $accessToken = new AccessToken();
        $user = new User();
        $user->setPhoneNumber('+12342567890');
        $user->setPassword('password');
        $accessToken->setUser($user);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($accessToken);

        $this->service->remove($accessToken);
    }

    public function testRemoveWithNull(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->service->remove(null);
    }

    public function testRemoveByUserWhenTokenExists(): void
    {
        $user = new User();
        $user->setPhoneNumber('+12342567890');
        $user->setPassword('password');

        $accessToken = new AccessToken();
        $accessToken->setUser($user);

        $this->repository
            ->expects($this->once())
            ->method('findOneByUser')
            ->with($user)
            ->willReturn($accessToken);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($accessToken);

        $this->service->removeByUser($user);
    }

    public function testRemoveByUserWhenTokenDoesNotExist(): void
    {
        $user = new User();
        $user->setPhoneNumber('+12234567890');
        $user->setPassword('password');

        $this->repository
            ->expects($this->once())
            ->method('findOneByUser')
            ->with($user)
            ->willReturn(null);

        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->service->removeByUser($user);
    }
}
