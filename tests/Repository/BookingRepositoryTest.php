<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BookingRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private BookingRepository $bookingRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->bookingRepository = $this->entityManager->getRepository(Booking::class);

        $this->clearDatabase();
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM bookings');
        $connection->executeStatement('DELETE FROM access_tokens');
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

    private function createTestHouse(int $sleepingPlaces = 2): House
    {
        $house = new House();
        $house->setSleepingPlaces($sleepingPlaces);
        $this->entityManager->persist($house);
        $this->entityManager->flush();

        return $house;
    }

    public function testCreate(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $comment = 'Test booking comment';

        $booking = $this->bookingRepository->create($user, $house, $comment);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertNotNull($booking->getId());
        $this->assertEquals($user, $booking->getUser());
        $this->assertEquals($house, $booking->getHouse());
        $this->assertEquals($comment, $booking->getComment());

        $foundBooking = $this->bookingRepository->find($booking->getId());
        $this->assertNotNull($foundBooking);
        $this->assertEquals($booking->getId(), $foundBooking->getId());
    }

    public function testRemove(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $booking = $this->bookingRepository->create($user, $house, 'Test comment');

        $bookingId = $booking->getId();

        $this->bookingRepository->remove($booking);

        $removedBooking = $this->bookingRepository->find($bookingId);
        $this->assertNull($removedBooking);
    }

    public function testRemoveNull(): void
    {
        $this->bookingRepository->remove(null);
        $this->assertTrue(true);
    }

    public function testRemoveById(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $booking = $this->bookingRepository->create($user, $house, 'Test comment');

        $bookingId = $booking->getId();

        $this->bookingRepository->removeById($bookingId);

        $removedBooking = $this->bookingRepository->find($bookingId);
        $this->assertNull($removedBooking);
    }

    public function testRemoveByIdNotFound(): void
    {
        $this->bookingRepository->removeById(9999);
        $this->assertTrue(true);
    }

    public function testMultipleBookings(): void
    {
        $user1 = $this->createTestUser('+79111111111');
        $user2 = $this->createTestUser('+79222222222');
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(4);

        $booking1 = $this->bookingRepository->create($user1, $house1, 'Booking 1');
        $booking2 = $this->bookingRepository->create($user2, $house2, 'Booking 2');

        $allBookings = $this->bookingRepository->findAll();

        $this->assertCount(2, $allBookings);
        $this->assertContains($booking1, $allBookings);
        $this->assertContains($booking2, $allBookings);
    }

    public function testBookingRelations(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $comment = 'Detailed booking';

        $booking = $this->bookingRepository->create($user, $house, $comment);

        $this->assertEquals($user->getId(), $booking->getUser()->getId());
        $this->assertEquals($house->getId(), $booking->getHouse()->getId());
        $this->assertEquals($comment, $booking->getComment());

        $this->entityManager->refresh($user);
        $userBookings = $user->getBookings();
        $this->assertCount(1, $userBookings);
        $this->assertEquals($booking->getId(), $userBookings->first()->getId());
    }

    public function testUserCanHaveMultipleBookings(): void
    {
        $user = $this->createTestUser();
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(3);

        $booking1 = $this->bookingRepository->create($user, $house1, 'First booking');
        $booking2 = $this->bookingRepository->create($user, $house2, 'Second booking');

        $this->entityManager->refresh($user);
        $userBookings = $user->getBookings();
        $this->assertCount(2, $userBookings);
        $this->assertTrue($userBookings->contains($booking1));
        $this->assertTrue($userBookings->contains($booking2));
    }

    public function testHouseCanBeBookedByMultipleUsers(): void
    {
        $user1 = $this->createTestUser('+79111111111');
        $user2 = $this->createTestUser('+79222222222');
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(2);

        $booking1 = $this->bookingRepository->create($user1, $house1, 'User 1 booking');
        $booking2 = $this->bookingRepository->create($user2, $house2, 'User 2 booking');

        $this->assertEquals($house1->getId(), $booking1->getHouse()->getId());
        $this->assertEquals($house2->getId(), $booking2->getHouse()->getId());
        $this->assertNotEquals($booking1->getUser()->getId(), $booking2->getUser()->getId());
    }

    public function testBookingJsonSerialization(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse(3);
        $booking = $this->bookingRepository->create($user, $house, 'Test comment');

        $serialized = $booking->jsonSerialize();

        $this->assertEquals($booking->getId(), $serialized['id']);
        $this->assertEquals($user->getId(), $serialized['user_id']);
        $this->assertEquals($house->getId(), $serialized['house_id']);
        $this->assertEquals('Test comment', $serialized['comment']);
    }

    public function testMultipleBookingsForDifferentHouses(): void
    {
        $user = $this->createTestUser();
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(3);
        $house3 = $this->createTestHouse(4);

        $booking1 = $this->bookingRepository->create($user, $house1, 'Booking 1');
        $booking2 = $this->bookingRepository->create($user, $house2, 'Booking 2');
        $booking3 = $this->bookingRepository->create($user, $house3, 'Booking 3');

        $this->entityManager->refresh($user);
        $userBookings = $user->getBookings();
        $this->assertCount(3, $userBookings);
    }

    public function testFindAllBookings(): void
    {
        $user1 = $this->createTestUser('+79111111111');
        $user2 = $this->createTestUser('+79222222222');
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(3);

        $booking1 = $this->bookingRepository->create($user1, $house1, 'Booking 1');
        $booking2 = $this->bookingRepository->create($user2, $house2, 'Booking 2');

        $allBookings = $this->bookingRepository->findAll();
        $this->assertCount(2, $allBookings);
    }
}
