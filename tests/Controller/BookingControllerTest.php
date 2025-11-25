<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AccessToken;
use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use DateTimeImmutable;
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
        $connection->executeStatement('DELETE FROM access_tokens');
        $connection->executeStatement('DELETE FROM houses');
        $connection->executeStatement('DELETE FROM users');
    }

    private function createTestUser(string $phoneNumber = '+79111111111', string $password = 'password'): User
    {
        $user = new User();
        $user->setPhoneNumber($phoneNumber);
        $user->setPassword(
            self::getContainer()->get('security.password_hasher')->hashPassword($user, $password)
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestAccessToken(User $user): AccessToken
    {
        $accessToken = new AccessToken();
        $accessToken->setUser($user);
        $accessToken->setExpiresAt(new DateTimeImmutable('+1 hour'));
        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();

        return $accessToken;
    }

    private function createTestHouse(int $sleepingPlaces = 2): House
    {
        $house = new House();
        $house->setSleepingPlaces($sleepingPlaces);
        $this->entityManager->persist($house);
        $this->entityManager->flush();

        return $house;
    }

    private function createTestBooking(User $user, House $house, string $comment = 'Test comment'): Booking
    {
        $booking = new Booking();
        $booking->setHouse($house);
        $booking->setComment($comment);
        $user->addBooking($booking);
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    private function getAuthHeaders(string $accessToken): array
    {
        return [
            'Content-Type' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ];
    }

    public function testGetUserBookingsSuccess(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($user);
        $house = $this->createTestHouse();
        $this->createTestBooking($user, $house, 'Test booking comment');

        $this->client->request(
            'GET',
            '/api/bookings/',
            [],
            [],
            $this->getAuthHeaders($accessToken->getValue())
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test booking comment', $data[0]['comment']);
    }

    public function testGetUserBookingsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/bookings/');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateBookingSuccess(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($user);
        $house = $this->createTestHouse();

        $payload = [
            'house_id' => $house->getId(),
            'comment' => 'Integration test booking'
        ];

        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            array_merge($this->getAuthHeaders($accessToken->getValue())),
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
    }

    public function testCreateBookingHouseNotFound(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($user);

        $payload = [
            'house_id' => 9999,
            'comment' => 'Test comment'
        ];

        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            array_merge($this->getAuthHeaders($accessToken->getValue())),
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateBookingWithoutAuthentication(): void
    {
        $house = $this->createTestHouse();

        $payload = [
            'house_id' => $house->getId(),
            'comment' => 'Test comment'
        ];

        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testEditBookingCommentSuccess(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($user);
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
            array_merge($this->getAuthHeaders($accessToken->getValue())),
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
    }

    public function testEditBookingCommentAccessDenied(): void
    {
        $user1 = $this->createTestUser('+79111111111');
        $user2 = $this->createTestUser('+79222222222');
        $accessToken2 = $this->createTestAccessToken($user2);
        $house = $this->createTestHouse();

        $booking = $this->createTestBooking($user1, $house, 'User 1 booking');

        $payload = [
            'comment' => 'Hacked comment'
        ];

        $this->client->request(
            'PATCH',
            "/api/bookings/{$booking->getId()}/comment",
            [],
            [],
            array_merge($this->getAuthHeaders($accessToken2->getValue())),
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testRemoveBookingSuccess(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($user);
        $house = $this->createTestHouse();
        $booking = $this->createTestBooking($user, $house);

        $this->client->request(
            'DELETE',
            "/api/bookings/{$booking->getId()}",
            [],
            [],
            $this->getAuthHeaders($accessToken->getValue())
        );

        $this->assertResponseIsSuccessful();
    }

    public function testRemoveBookingAccessDenied(): void
    {
        $user1 = $this->createTestUser('+79111111111');
        $user2 = $this->createTestUser('+79222222222');
        $accessToken2 = $this->createTestAccessToken($user2);
        $house = $this->createTestHouse();

        $booking = $this->createTestBooking($user1, $house);

        $this->client->request(
            'DELETE',
            "/api/bookings/{$booking->getId()}",
            [],
            [],
            $this->getAuthHeaders($accessToken2->getValue())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }
}
