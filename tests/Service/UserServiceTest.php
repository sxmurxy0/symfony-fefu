<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $repository;
    private UserPasswordHasherInterface $passwordHasher;
    private UserService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->service = new UserService($this->entityManager, $this->repository, $this->passwordHasher);
    }

    public function testCreate(): void
    {
        $phoneNumber = '+1234567890';
        $plainPassword = 'password123';
        $hashedPassword = 'hashed_password_123';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), $plainPassword)
            ->willReturn($hashedPassword);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $user = $this->service->create($phoneNumber, $plainPassword);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($phoneNumber, $user->getPhoneNumber());
        $this->assertSame($hashedPassword, $user->getPassword());
    }

    public function testRemoveWithUser(): void
    {
        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('password');

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->service->remove($user);
    }

    public function testRemoveWithNull(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->service->remove(null);
    }

    public function testRemoveByIdWhenUserExists(): void
    {
        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('password');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->service->removeById(1);
    }

    public function testRemoveByIdWhenUserDoesNotExist(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->service->removeById(999);
    }

    public function testIsPasswordValid(): void
    {
        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('hashed_password');
        $plainPassword = 'password123';

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $plainPassword)
            ->willReturn(true);

        $isValid = $this->service->isPasswordValid($user, $plainPassword);

        $this->assertTrue($isValid);
    }

    public function testIsPasswordInvalid(): void
    {
        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('hashed_password');
        $plainPassword = 'wrong_password';

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $plainPassword)
            ->willReturn(false);

        $isValid = $this->service->isPasswordValid($user, $plainPassword);

        $this->assertFalse($isValid);
    }
}
