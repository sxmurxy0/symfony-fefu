<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
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
        $connection->executeStatement('DELETE FROM access_tokens');
        $connection->executeStatement('DELETE FROM bookings');
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

    private function getAuthHeaders(string $accessToken): array
    {
        return [
            'Content-Type' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ];
    }

    public function testRegisterSuccess(): void
    {
        $payload = [
            'phone_number' => '+79111111111',
            'password' => 'securepassword123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('value', $data);
        $this->assertNotEmpty($data['value']);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['phoneNumber' => '+79111111111']);
        $this->assertNotNull($user);
        $this->assertEquals('+79111111111', $user->getPhoneNumber());
    }

    public function testRegisterUserAlreadyExists(): void
    {
        $this->createTestUser('+79111111111', 'password');

        $payload = [
            'phone_number' => '+79111111111',
            'password' => 'newpassword'
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testLoginSuccess(): void
    {
        $this->createTestUser('+79111111111', 'password');

        $payload = [
            'phone_number' => '+79111111111',
            'password' => 'password'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('value', $data);
        $this->assertNotEmpty($data['value']);
    }

    public function testLoginUserNotFound(): void
    {
        $payload = [
            'phone_number' => '+79999999999',
            'password' => 'password'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testLoginWrongPassword(): void
    {
        $this->createTestUser('+79111111111', 'correctpassword');

        $payload = [
            'phone_number' => '+79111111111',
            'password' => 'wrongpassword'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testEndToEndAuthFlow(): void
    {
        $registerPayload = [
            'phone_number' => '+79111111111',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($registerPayload)
        );

        $this->assertResponseIsSuccessful();
        $registerResponse = json_decode($this->client->getResponse()->getContent(), true);
        $accessToken = $registerResponse['value'];

        $loginPayload = [
            'phone_number' => '+79111111111',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($loginPayload)
        );
        $this->assertResponseIsSuccessful();
        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($loginResponse['value']);
    }
}
