<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AccessToken;
use App\Entity\House;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HouseControllerTest extends WebTestCase
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

    private function createTestUser(
        string $phoneNumber = '+79111111111',
        string $password = 'password',
        array $roles = ['ROLE_USER']
    ): User {
        $user = new User();
        $user->setPhoneNumber($phoneNumber);
        $user->setPassword(
            self::getContainer()->get('security.password_hasher')->hashPassword($user, $password)
        );
        $user->setRoles($roles);
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

    private function getAuthHeaders(string $accessToken): array
    {
        return [
            'Content-Type' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ];
    }

    public function testGetAllHousesAsAdmin(): void
    {
        $adminUser = $this->createTestUser('+79111111111', 'password', ['ROLE_ADMIN']);
        $accessToken = $this->createTestAccessToken($adminUser);

        $this->createTestHouse(2);
        $this->createTestHouse(4);

        $this->client->request(
            'GET',
            '/api/houses/',
            [],
            [],
            $this->getAuthHeaders($accessToken->getValue())
        );

        $this->assertResponseIsSuccessful();
    }

    public function testGetAllHousesAsRegularUser(): void
    {
        $regularUser = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($regularUser);

        $this->client->request(
            'GET',
            '/api/houses/',
            [],
            [],
            $this->getAuthHeaders($accessToken->getValue())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetAvailableHousesAsUser(): void
    {
        $user = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($user);

        $this->createTestHouse(2);

        $this->client->request(
            'GET',
            '/api/houses/available',
            [],
            [],
            $this->getAuthHeaders($accessToken->getValue())
        );

        $this->assertResponseIsSuccessful();
    }

    public function testCreateHouseAsAdmin(): void
    {
        $adminUser = $this->createTestUser('+79111111111', 'password', ['ROLE_ADMIN']);
        $accessToken = $this->createTestAccessToken($adminUser);

        $payload = [
            'sleeping_places' => 3
        ];

        $this->client->request(
            'POST',
            '/api/houses/create',
            [],
            [],
            array_merge($this->getAuthHeaders($accessToken->getValue())),
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
    }

    public function testCreateHouseAsRegularUser(): void
    {
        $regularUser = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($regularUser);

        $payload = [
            'sleeping_places' => 3
        ];

        $this->client->request(
            'POST',
            '/api/houses/create',
            [],
            [],
            array_merge($this->getAuthHeaders($accessToken->getValue())),
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRemoveHouseAsAdmin(): void
    {
        $adminUser = $this->createTestUser('+79111111111', 'password', ['ROLE_ADMIN']);
        $accessToken = $this->createTestAccessToken($adminUser);
        $house = $this->createTestHouse(2);

        $this->client->request(
            'DELETE',
            "/api/houses/{$house->getId()}",
            [],
            [],
            $this->getAuthHeaders($accessToken->getValue())
        );

        $this->assertResponseIsSuccessful();
    }

    public function testRemoveHouseAsRegularUser(): void
    {
        $regularUser = $this->createTestUser();
        $accessToken = $this->createTestAccessToken($regularUser);
        $house = $this->createTestHouse(2);

        $this->client->request(
            'DELETE',
            "/api/houses/{$house->getId()}",
            [],
            [],
            $this->getAuthHeaders($accessToken->getValue())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
