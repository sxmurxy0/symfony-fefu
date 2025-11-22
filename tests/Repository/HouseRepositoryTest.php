<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HouseRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private HouseRepository $houseRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->houseRepository = $this->entityManager->getRepository(House::class);

        $this->clearDatabase();
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM access_tokens');
        $connection->executeStatement('DELETE FROM bookings');
        $connection->executeStatement('DELETE FROM houses');
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

    private function createTestBooking(User $user, House $house, string $comment = 'Test comment'): Booking
    {
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setHouse($house);
        $booking->setComment($comment);
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    public function testCreate(): void
    {
        $sleepingPlaces = 3;

        $house = $this->houseRepository->create($sleepingPlaces);

        $this->assertInstanceOf(House::class, $house);
        $this->assertNotNull($house->getId());
        $this->assertEquals($sleepingPlaces, $house->getSleepingPlaces());

        $foundHouse = $this->houseRepository->find($house->getId());
        $this->assertNotNull($foundHouse);
        $this->assertEquals($house->getId(), $foundHouse->getId());
    }

    public function testRemove(): void
    {
        $house = $this->houseRepository->create(2);

        $houseId = $house->getId();

        $this->houseRepository->remove($house);

        $removedHouse = $this->houseRepository->find($houseId);
        $this->assertNull($removedHouse);
    }

    public function testRemoveNull(): void
    {
        $this->houseRepository->remove(null);
        $this->assertTrue(true);
    }

    public function testRemoveById(): void
    {
        $house = $this->houseRepository->create(2);

        $houseId = $house->getId();

        $this->houseRepository->removeById($houseId);

        $removedHouse = $this->houseRepository->find($houseId);
        $this->assertNull($removedHouse);
    }

    public function testRemoveByIdNotFound(): void
    {
        $this->houseRepository->removeById(9999);
        $this->assertTrue(true);
    }

    public function testFindAvailableWhenNoBookings(): void
    {
        $house1 = $this->houseRepository->create(2);
        $house2 = $this->houseRepository->create(4);

        $availableHouses = $this->houseRepository->findAvailable();

        $this->assertCount(2, $availableHouses);
        $this->assertContains($house1, $availableHouses);
        $this->assertContains($house2, $availableHouses);
    }

    public function testFindAvailableWithBookings(): void
    {
        $house1 = $this->houseRepository->create(2);
        $house2 = $this->houseRepository->create(4);
        $user = $this->createTestUser();

        $this->createTestBooking($user, $house1);

        $availableHouses = $this->houseRepository->findAvailable();

        $this->assertCount(1, $availableHouses);
        $this->assertContains($house2, $availableHouses);
        $this->assertNotContains($house1, $availableHouses);
    }

    public function testFindAvailableWhenAllBooked(): void
    {
        $house1 = $this->houseRepository->create(2);
        $house2 = $this->houseRepository->create(4);
        $user = $this->createTestUser();

        $this->createTestBooking($user, $house1);
        $this->createTestBooking($user, $house2);

        $availableHouses = $this->houseRepository->findAvailable();

        $this->assertCount(0, $availableHouses);
    }

    public function testIsAvailableWhenAvailable(): void
    {
        $house = $this->houseRepository->create(2);

        $isAvailable = $this->houseRepository->isAvailable($house->getId());

        $this->assertTrue($isAvailable);
    }

    public function testIsAvailableWhenBooked(): void
    {
        $house = $this->houseRepository->create(2);
        $user = $this->createTestUser();

        $this->createTestBooking($user, $house);

        $isAvailable = $this->houseRepository->isAvailable($house->getId());

        $this->assertFalse($isAvailable);
    }

    public function testIsAvailableWithNonExistentHouse(): void
    {
        $isAvailable = $this->houseRepository->isAvailable(9999);

        $this->assertTrue($isAvailable);
    }

    public function testMultipleHousesMixedAvailability(): void
    {
        $house1 = $this->houseRepository->create(2);
        $house2 = $this->houseRepository->create(3);
        $house3 = $this->houseRepository->create(4);
        $user = $this->createTestUser();

        $this->createTestBooking($user, $house1);
        $this->createTestBooking($user, $house3);

        $availableHouses = $this->houseRepository->findAvailable();

        $this->assertCount(1, $availableHouses);
        $this->assertContains($house2, $availableHouses);
        $this->assertNotContains($house1, $availableHouses);
        $this->assertNotContains($house3, $availableHouses);

        $this->assertFalse($this->houseRepository->isAvailable($house1->getId()));
        $this->assertTrue($this->houseRepository->isAvailable($house2->getId()));
        $this->assertFalse($this->houseRepository->isAvailable($house3->getId()));
    }
}
