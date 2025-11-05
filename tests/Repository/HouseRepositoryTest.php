<?php

namespace App\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\House;
use App\Entity\User;
use App\Entity\Booking;
use App\Repository\HouseRepository;

class HouseRepositoryTest extends KernelTestCase {

    private EntityManagerInterface $entityManager;
    private HouseRepository $houseRepository;

    protected function setUp(): void {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->houseRepository = $this->entityManager->getRepository(House::class);
        
        $this->clearDatabase();
    }

    private function clearDatabase(): void {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM bookings');
        $connection->executeStatement('DELETE FROM houses');
        $connection->executeStatement('DELETE FROM users');
    }

    private function createTestHouse(int $sleepingPlaces = 2): House {
        $house = new House($sleepingPlaces);
        $this->entityManager->persist($house);
        $this->entityManager->flush();

        return $house;
    }

    private function createTestUser(string $phoneNumber = '+79111111111'): User {
        $user = new User($phoneNumber);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestBooking(User $user, House $house, string $comment = 'Test comment'): Booking {
        $booking = new Booking($user, $house, $comment);
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    public function testFindAvailableWhenNoBookings(): void {
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(4);

        $availableHouses = $this->houseRepository->findAvailable();

        $this->assertCount(2, $availableHouses);
        $this->assertContains($house1, $availableHouses);
        $this->assertContains($house2, $availableHouses);
    }

    public function testFindAvailableWithBookings(): void {
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(4);
        $user = $this->createTestUser();

        $this->createTestBooking($user, $house1);

        $availableHouses = $this->houseRepository->findAvailable();

        $this->assertCount(1, $availableHouses);
        $this->assertContains($house2, $availableHouses);
        $this->assertNotContains($house1, $availableHouses);
    }

    public function testFindAvailableWhenAllBooked(): void {
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(4);
        $user = $this->createTestUser();

        $this->createTestBooking($user, $house1);
        $this->createTestBooking($user, $house2);

        $availableHouses = $this->houseRepository->findAvailable();

        $this->assertCount(0, $availableHouses);
    }

    public function testIsHouseAvailableWhenAvailable(): void {
        $house = $this->createTestHouse();

        $isAvailable = $this->houseRepository->isHouseAvailable($house->getId());

        $this->assertTrue($isAvailable);
    }

    public function testIsHouseAvailableWhenBooked(): void {
        $house = $this->createTestHouse();
        $user = $this->createTestUser();

        $this->createTestBooking($user, $house);

        $isAvailable = $this->houseRepository->isHouseAvailable($house->getId());

        $this->assertFalse($isAvailable);
    }

    public function testIsHouseAvailableWithNonExistentHouse(): void {
        $isAvailable = $this->houseRepository->isHouseAvailable(9999);

        $this->assertTrue($isAvailable);
    }

}