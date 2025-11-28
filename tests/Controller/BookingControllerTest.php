<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookingControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->clearDatabase();
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM bookings');
        $connection->executeStatement('DELETE FROM houses');
        $connection->executeStatement('DELETE FROM users');
    }

    private function createTestUser(string $phoneNumber = '+79111111111'): User
    {
        $user = new User($phoneNumber);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestHouse(bool $isAvailable = true): House
    {
        $house = new House(2);

        $this->entityManager->persist($house);
        $this->entityManager->flush();

        return $house;
    }

    private function createTestBooking(User $user, House $house, string $comment = 'Test comment'): Booking
    {
        $booking = new Booking($user, $house, $comment);
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    public function testGetAllBookings(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $booking = $this->createTestBooking($user, $house);

        $this->client->request('GET', '/api/bookings');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($booking->getId(), $data[0]['id']);
        $this->assertEquals('Test comment', $data[0]['comment']);
    }

    public function testGetAllBookingsWhenEmpty(): void
    {
        $this->client->request('GET', '/api/bookings');

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    public function testCreateBookingSuccess(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();

        $payload = [
            'user_id' => $user->getId(),
            'house_id' => $house->getId(),
            'comment' => 'Integration test booking'
        ];

        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($user->getId(), $data['user_id']);
        $this->assertEquals($house->getId(), $data['house_id']);
        $this->assertEquals('Integration test booking', $data['comment']);

        $booking = $this->entityManager->getRepository(Booking::class)->find($data['id']);
        $this->assertNotNull($booking);
        $this->assertEquals('Integration test booking', $booking->getComment());
    }

    public function testCreateBookingMissingParameters(): void
    {
        $payload = [
            'user_id' => 1,
        ];

        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateBookingUserNotFound(): void
    {
        $house = $this->createTestHouse();

        $payload = [
            'user_id' => 9999,
            'house_id' => $house->getId(),
            'comment' => 'Test comment'
        ];

        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateBookingHouseNotFound(): void
    {
        $user = $this->createTestUser();

        $payload = [
            'user_id' => $user->getId(),
            'house_id' => 9999,
            'comment' => 'Test comment'
        ];

        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testEditBookingCommentSuccess(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $booking = $this->createTestBooking($user, $house, 'Old comment');

        $payload = [
            'comment' => 'Updated comment'
        ];

        $this->client->request(
            'PATCH',
            "/api/bookings/{$booking->getId()}/comment",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $updatedBooking = $this->entityManager->getRepository(Booking::class)->find($booking->getId());
        $this->assertEquals('Updated comment', $updatedBooking->getComment());
    }

    public function testEditBookingCommentMissingComment(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $booking = $this->createTestBooking($user, $house);

        $payload = [
        ];

        $this->client->request(
            'PATCH',
            "/api/bookings/{$booking->getId()}/comment",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testEditBookingCommentNotFound(): void
    {
        $payload = [
            'comment' => 'Updated comment'
        ];

        $this->client->request(
            'PATCH',
            '/api/bookings/9999/comment',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteBookingSuccess(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();
        $booking = $this->createTestBooking($user, $house);

        $bookingId = $booking->getId();

        $this->client->request('DELETE', "/api/bookings/{$bookingId}");

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $deletedBooking = $this->entityManager->getRepository(Booking::class)->find($bookingId);
        $this->assertNull($deletedBooking);
    }

    public function testDeleteBookingNotFound(): void
    {
        $this->client->request('DELETE', '/api/bookings/9999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
