<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $repository;
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->registry = self::getContainer()->get(ManagerRegistry::class);
        $this->entityManager = $this->registry->getManager();
        $this->repository = new UserRepository($this->registry);

        $this->entityManager->getConnection()->executeQuery('DELETE FROM access_tokens');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM bookings');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM houses');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM users');
    }

    public function testFindOneByPhoneNumber(): void
    {
        $phoneNumber = '+1234567890';
        $user = new User();
        $user->setPhoneNumber($phoneNumber);
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->findOneByPhoneNumber($phoneNumber);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($phoneNumber, $foundUser->getPhoneNumber());
        $this->assertEquals($user->getId(), $foundUser->getId());
    }

    public function testFindOneByPhoneNumberReturnsNullWhenNotFound(): void
    {
        $nonExistentPhoneNumber = '+9999999999';

        $foundUser = $this->repository->findOneByPhoneNumber($nonExistentPhoneNumber);

        $this->assertNull($foundUser);
    }

    public function testExistsWithPhoneNumberReturnsTrueWhenUserExists(): void
    {
        $phoneNumber = '+1234567890';
        $user = new User();
        $user->setPhoneNumber($phoneNumber);
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $exists = $this->repository->existsWithPhoneNumber($phoneNumber);

        $this->assertTrue($exists);
    }

    public function testExistsWithPhoneNumberReturnsFalseWhenUserDoesNotExist(): void
    {
        $nonExistentPhoneNumber = '+9999999999';

        $exists = $this->repository->existsWithPhoneNumber($nonExistentPhoneNumber);

        $this->assertFalse($exists);
    }
}
