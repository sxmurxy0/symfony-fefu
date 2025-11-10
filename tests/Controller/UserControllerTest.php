<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
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
        $connection->executeStatement('DELETE FROM users');
    }

    private function createTestUser(string $phoneNumber = '+79111111111'): User
    {
        $user = new User($phoneNumber);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testGetAllUsers(): void
    {
        $user1 = $this->createTestUser('+79111111111');
        $user2 = $this->createTestUser('+79222222222');

        $this->client->request('GET', '/api/users');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function testGetAllUsersWhenEmpty(): void
    {
        $this->client->request('GET', '/api/users');

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    public function testCreateUserSuccess(): void
    {
        $payload = [
            'phone_number' => '+79998887766'
        ];

        $this->client->request(
            'POST',
            '/api/users/create',
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
        $this->assertEquals('+79998887766', $data['phone_number']);

        $user = $this->entityManager->getRepository(User::class)->find($data['id']);
        $this->assertNotNull($user);
        $this->assertEquals('+79998887766', $user->getPhoneNumber());
    }

    public function testCreateUserMissingParameters(): void
    {
        $payload = [];

        $this->client->request(
            'POST',
            '/api/users/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testDeleteUserSuccess(): void
    {
        $user = $this->createTestUser();
        $userId = $user->getId();

        $this->client->request('DELETE', "/api/users/{$userId}");

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $deletedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertNull($deletedUser);
    }

    public function testDeleteUserNotFound(): void
    {
        $this->client->request('DELETE', '/api/users/9999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
