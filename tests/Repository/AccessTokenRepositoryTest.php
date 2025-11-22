<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessTokenRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AccessTokenRepository $accessTokenRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->accessTokenRepository = $this->entityManager->getRepository(AccessToken::class);

        $this->clearDatabase();
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM access_tokens');
        $connection->executeStatement('DELETE FROM users');
    }

    private function createTestUser(string $phoneNumber = '+79111111111'): User
    {
        $user = new User();
        $user->setPhoneNumber($phoneNumber);
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testCreate(): void
    {
        $user = $this->createTestUser();
        $expiresAt = new DateTimeImmutable('+7 days');

        $accessToken = $this->accessTokenRepository->create($user, $expiresAt);

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertNotNull($accessToken->getValue());
        $this->assertEquals($user, $accessToken->getUser());
        $this->assertEquals($expiresAt, $accessToken->getExpiresAt());

        $foundToken = $this->accessTokenRepository->find($accessToken->getId());
        $this->assertNotNull($foundToken);
        $this->assertEquals($accessToken->getValue(), $foundToken->getValue());
    }

    public function testCreateWithDefaultExpiration(): void
    {
        $user = $this->createTestUser();

        $accessToken = $this->accessTokenRepository->create($user);

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertNotNull($accessToken->getExpiresAt());
        $this->assertGreaterThan(new DateTimeImmutable(), $accessToken->getExpiresAt());
    }

    public function testRemove(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->accessTokenRepository->create($user);

        $tokenId = $accessToken->getId();

        $this->accessTokenRepository->remove($accessToken);

        $removedToken = $this->accessTokenRepository->find($tokenId);
        $this->assertNull($removedToken);
    }

    public function testRemoveNull(): void
    {
        $this->accessTokenRepository->remove(null);
        $this->assertTrue(true);
    }

    public function testRemoveByUser(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->accessTokenRepository->create($user);
        $accessTokenId = $accessToken->getId();

        $this->accessTokenRepository->removeByUser($user);

        $removedToken = $this->accessTokenRepository->find($accessTokenId);
        $this->assertNull($removedToken);
    }

    public function testRemoveByUserWhenNoToken(): void
    {
        $user = $this->createTestUser();
        $this->accessTokenRepository->removeByUser($user);
        $this->assertTrue(true);
    }

    public function testFindOneByValue(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->accessTokenRepository->create($user);

        $foundToken = $this->accessTokenRepository->findOneByValue($accessToken->getValue());

        $this->assertNotNull($foundToken);
        $this->assertEquals($accessToken->getId(), $foundToken->getId());
        $this->assertEquals($accessToken->getValue(), $foundToken->getValue());
    }

    public function testFindOneByValueNotFound(): void
    {
        $foundToken = $this->accessTokenRepository->findOneByValue('non_existent_value');
        $this->assertNull($foundToken);
    }

    public function testFindOneByUser(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->accessTokenRepository->create($user);

        $foundToken = $this->accessTokenRepository->findOneByUser($user);

        $this->assertNotNull($foundToken);
        $this->assertEquals($accessToken->getId(), $foundToken->getId());
        $this->assertEquals($user, $foundToken->getUser());
    }

    public function testFindOneByUserNotFound(): void
    {
        $user = $this->createTestUser();
        $foundToken = $this->accessTokenRepository->findOneByUser($user);
        $this->assertNull($foundToken);
    }

    public function testTokenUniqueness(): void
    {
        $user1 = $this->createTestUser('+79111111111');
        $user2 = $this->createTestUser('+79222222222');

        $token1 = $this->accessTokenRepository->create($user1);
        $token2 = $this->accessTokenRepository->create($user2);

        $this->assertNotEquals($token1->getValue(), $token2->getValue());

        $foundToken1 = $this->accessTokenRepository->findOneByValue($token1->getValue());
        $foundToken2 = $this->accessTokenRepository->findOneByValue($token2->getValue());

        $this->assertEquals($token1->getId(), $foundToken1->getId());
        $this->assertEquals($token2->getId(), $foundToken2->getId());
        $this->assertNotEquals($foundToken1->getId(), $foundToken2->getId());
    }

    public function testTokenValidity(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->accessTokenRepository->create($user);

        $this->assertTrue($accessToken->isValid());
    }

    public function testTokenExpiration(): void
    {
        $user = $this->createTestUser();
        $expiresAt = new DateTimeImmutable('-1 hour');
        $accessToken = $this->accessTokenRepository->create($user, $expiresAt);

        $this->assertFalse($accessToken->isValid());
    }

    public function testTokenUserRelationship(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->accessTokenRepository->create($user);

        $this->assertEquals($user->getId(), $accessToken->getUser()->getId());
        $this->assertEquals($user->getPhoneNumber(), $accessToken->getUser()->getPhoneNumber());
    }
}
