<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessTokenRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AccessTokenRepository $repository;
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->registry = self::getContainer()->get(ManagerRegistry::class);
        $this->entityManager = $this->registry->getManager();
        $this->repository = new AccessTokenRepository($this->registry);

        $this->entityManager->getConnection()->executeQuery('DELETE FROM access_tokens');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM bookings');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM houses');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM users');
    }

    public function testFindOneByValue(): void
    {
        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $accessToken = new AccessToken();
        $accessToken->setUser($user);
        $accessToken->setExpiresAt(new DateTimeImmutable('+1 day'));

        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();

        $tokenValue = $accessToken->getValue();

        $foundToken = $this->repository->findOneByValue($tokenValue);

        $this->assertInstanceOf(AccessToken::class, $foundToken);
        $this->assertEquals($tokenValue, $foundToken->getValue());
        $this->assertEquals($user->getId(), $foundToken->getUser()->getId());
    }

    public function testFindOneByValueReturnsNullWhenNotFound(): void
    {
        $nonExistentTokenValue = 'non_existent_token';

        $foundToken = $this->repository->findOneByValue($nonExistentTokenValue);

        $this->assertNull($foundToken);
    }

    public function testFindOneByUser(): void
    {
        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $accessToken = new AccessToken();
        $accessToken->setUser($user);
        $accessToken->setExpiresAt(new DateTimeImmutable('+1 day'));
        $accessToken->generateValue();

        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();

        $foundToken = $this->repository->findOneByUser($user);

        $this->assertInstanceOf(AccessToken::class, $foundToken);
        $this->assertEquals($user->getId(), $foundToken->getUser()->getId());
        $this->assertEquals($accessToken->getValue(), $foundToken->getValue());
    }

    public function testFindOneByUserReturnsNullWhenNotFound(): void
    {
        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $anotherUser = new User();
        $anotherUser->setPhoneNumber('+0987654321');
        $anotherUser->setPassword('password');
        $this->entityManager->persist($anotherUser);

        $anotherToken = new AccessToken();
        $anotherToken->setUser($anotherUser);
        $anotherToken->setExpiresAt(new DateTimeImmutable('+1 day'));
        $anotherToken->generateValue();

        $this->entityManager->persist($anotherToken);
        $this->entityManager->flush();

        $foundToken = $this->repository->findOneByUser($user);

        $this->assertNull($foundToken);
    }
}
