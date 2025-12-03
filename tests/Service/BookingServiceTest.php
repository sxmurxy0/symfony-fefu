<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BookingServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookingRepository $repository;
    private BookingService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(BookingRepository::class);
        $this->service = new BookingService($this->entityManager, $this->repository);
    }

    public function testCreate(): void
    {
        $user = new User();
        $user->setPhoneNumber('+12324567890');
        $user->setPassword('password');

        $house = new House();
        $house->setSleepingPlaces(2);

        $comment = 'Test booking comment';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Booking::class));

        $booking = $this->service->create($user, $house, $comment);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame($user, $booking->getUser());
        $this->assertSame($house, $booking->getHouse());
        $this->assertSame($comment, $booking->getComment());
        $this->assertTrue($user->getBookings()->contains($booking));
    }

    public function testRemoveWithBooking(): void
    {
        $booking = new Booking();
        $user = new User();
        $user->setPhoneNumber('+12345678920');
        $user->setPassword('password');
        $house = new House();
        $house->setSleepingPlaces(2);
        $booking->setUser($user);
        $booking->setHouse($house);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($booking);

        $this->service->remove($booking);
    }

    public function testRemoveWithNull(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->service->remove(null);
    }

    public function testRemoveByIdWhenBookingExists(): void
    {
        $booking = new Booking();
        $user = new User();
        $user->setPhoneNumber('+12324567890');
        $user->setPassword('password');
        $house = new House();
        $house->setSleepingPlaces(2);
        $booking->setUser($user);
        $booking->setHouse($house);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($booking);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($booking);

        $this->service->removeById(1);
    }

    public function testRemoveByIdWhenBookingDoesNotExist(): void
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
}
