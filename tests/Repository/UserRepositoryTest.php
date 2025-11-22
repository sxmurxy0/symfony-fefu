<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);

        $this->clearDatabase();
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM access_tokens');
        $connection->executeStatement('DELETE FROM bookings');
        $connection->executeStatement('DELETE FROM users');
    }

    public function testCreate(): void
    {
        $phoneNumber = '+79111111111';
        $password = 'securepassword123';

        $user = $this->userRepository->create($phoneNumber, $password);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->getId());
        $this->assertEquals($phoneNumber, $user->getPhoneNumber());
        $this->assertNotEquals($password, $user->getPassword());

        $foundUser = $this->userRepository->find($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->getId(), $foundUser->getId());
    }

    public function testRemove(): void
    {
        $user = $this->userRepository->create('+79111111111', 'password');

        $userId = $user->getId();

        $this->userRepository->remove($user);

        $removedUser = $this->userRepository->find($userId);
        $this->assertNull($removedUser);
    }

    public function testRemoveNull(): void
    {
        $this->userRepository->remove(null);
        $this->assertTrue(true);
    }

    public function testRemoveById(): void
    {
        $user = $this->userRepository->create('+79111111111', 'password');

        $userId = $user->getId();

        $this->userRepository->removeById($userId);

        $removedUser = $this->userRepository->find($userId);
        $this->assertNull($removedUser);
    }

    public function testRemoveByIdNotFound(): void
    {
        $this->userRepository->removeById(9999);
        $this->assertTrue(true);
    }

    public function testFindOneByPhoneNumber(): void
    {
        $phoneNumber = '+79111111111';
        $user = $this->userRepository->create($phoneNumber, 'password');

        $foundUser = $this->userRepository->findOneByPhoneNumber($phoneNumber);

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->getId(), $foundUser->getId());
        $this->assertEquals($phoneNumber, $foundUser->getPhoneNumber());
    }

    public function testFindOneByPhoneNumberNotFound(): void
    {
        $foundUser = $this->userRepository->findOneByPhoneNumber('+79999999999');
        $this->assertNull($foundUser);
    }

    public function testExistsWithPhoneNumberWhenExists(): void
    {
        $phoneNumber = '+79111111111';
        $this->userRepository->create($phoneNumber, 'password');

        $exists = $this->userRepository->existsWithPhoneNumber($phoneNumber);

        $this->assertTrue($exists);
    }

    public function testExistsWithPhoneNumberWhenNotExists(): void
    {
        $exists = $this->userRepository->existsWithPhoneNumber('+79999999999');
        $this->assertFalse($exists);
    }

    public function testMultipleUsers(): void
    {
        $user1 = $this->userRepository->create('+79111111111', 'password1');
        $user2 = $this->userRepository->create('+79222222222', 'password2');

        $foundUser1 = $this->userRepository->findOneByPhoneNumber('+79111111111');
        $foundUser2 = $this->userRepository->findOneByPhoneNumber('+79222222222');

        $this->assertNotNull($foundUser1);
        $this->assertNotNull($foundUser2);
        $this->assertEquals($user1->getId(), $foundUser1->getId());
        $this->assertEquals($user2->getId(), $foundUser2->getId());
        $this->assertNotEquals($foundUser1->getId(), $foundUser2->getId());

        $this->assertTrue($this->userRepository->existsWithPhoneNumber('+79111111111'));
        $this->assertTrue($this->userRepository->existsWithPhoneNumber('+79222222222'));
        $this->assertFalse($this->userRepository->existsWithPhoneNumber('+79333333333'));
    }

    public function testPasswordHashing(): void
    {
        $password = 'plainpassword';
        $user = $this->userRepository->create('+79111111111', $password);

        $this->assertNotEquals($password, $user->getPassword());
        $this->assertNotEmpty($user->getPassword());

        $passwordHasher = self::getContainer()->get('security.password_hasher');
        $this->assertTrue($passwordHasher->isPasswordValid($user, $password));
        $this->assertFalse($passwordHasher->isPasswordValid($user, 'wrongpassword'));
    }

    public function testUserRoles(): void
    {
        $user = $this->userRepository->create('+79111111111', 'password');

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testUserJsonSerialization(): void
    {
        $user = $this->userRepository->create('+79111111111', 'password');

        $serialized = $user->jsonSerialize();

        $this->assertEquals($user->getId(), $serialized['id']);
        $this->assertEquals('+79111111111', $serialized['phone_number']);
        $this->assertArrayHasKey('bookings_count', $serialized);
    }
}
