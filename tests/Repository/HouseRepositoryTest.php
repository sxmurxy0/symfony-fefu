<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HouseRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private HouseRepository $repository;
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->registry = self::getContainer()->get(ManagerRegistry::class);
        $this->entityManager = $this->registry->getManager();
        $this->repository = new HouseRepository($this->registry);

        $this->entityManager->getConnection()->executeQuery('DELETE FROM access_tokens');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM bookings');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM houses');
        $this->entityManager->getConnection()->executeQuery('DELETE FROM users');
    }

    public function testFindAvailableWhenNoBookings(): void
    {
        $house1 = new House();
        $house1->setSleepingPlaces(2);
        $this->entityManager->persist($house1);

        $house2 = new House();
        $house2->setSleepingPlaces(4);
        $this->entityManager->persist($house2);

        $this->entityManager->flush();

        $availableHouses = $this->repository->findAvailable();

        $this->assertCount(2, $availableHouses);
        $this->assertContains($house1, $availableHouses);
        $this->assertContains($house2, $availableHouses);
    }

    public function testFindAvailableWithBookings(): void
    {
        $house1 = new House();
        $house1->setSleepingPlaces(2);
        $this->entityManager->persist($house1);

        $house2 = new House();
        $house2->setSleepingPlaces(4);
        $this->entityManager->persist($house2);

        $house3 = new House();
        $house3->setSleepingPlaces(3);
        $this->entityManager->persist($house3);

        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $booking1 = new Booking();
        $booking1->setUser($user);
        $booking1->setHouse($house1);
        $booking1->setComment('Test booking 1');
        $this->entityManager->persist($booking1);

        $booking2 = new Booking();
        $booking2->setUser($user);
        $booking2->setHouse($house3);
        $booking2->setComment('Test booking 2');
        $this->entityManager->persist($booking2);

        $this->entityManager->flush();

        $availableHouses = $this->repository->findAvailable();

        $this->assertCount(1, $availableHouses);
        $this->assertContains($house2, $availableHouses);
        $this->assertNotContains($house1, $availableHouses);
        $this->assertNotContains($house3, $availableHouses);
    }

    public function testIsAvailableWhenHouseIsAvailable(): void
    {
        $house = new House();
        $house->setSleepingPlaces(2);
        $this->entityManager->persist($house);
        $this->entityManager->flush();

        $isAvailable = $this->repository->isAvailable($house->getId());

        $this->assertTrue($isAvailable);
    }

    public function testIsAvailableWhenHouseIsBooked(): void
    {
        $house = new House();
        $house->setSleepingPlaces(2);
        $this->entityManager->persist($house);

        $user = new User();
        $user->setPhoneNumber('+1234567890');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $booking = new Booking();
        $booking->setUser($user);
        $booking->setHouse($house);
        $booking->setComment('Test booking');
        $this->entityManager->persist($booking);

        $this->entityManager->flush();

        $isAvailable = $this->repository->isAvailable($house->getId());

        $this->assertFalse($isAvailable);
    }
}
