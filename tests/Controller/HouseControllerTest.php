<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\House;
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
        $connection->executeStatement('DELETE FROM houses');
    }

    private function createTestHouse(int $sleepingPlaces = 2): House
    {
        $house = new House($sleepingPlaces);
        $this->entityManager->persist($house);
        $this->entityManager->flush();

        return $house;
    }

    public function testGetAllHouses(): void
    {
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(4);

        $this->client->request('GET', '/api/houses');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function testGetAllHousesWhenEmpty(): void
    {
        $this->client->request('GET', '/api/houses');

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    public function testGetAvailableHouses(): void
    {
        $house1 = $this->createTestHouse(2);
        $house2 = $this->createTestHouse(4);

        $this->client->request('GET', '/api/houses/available');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
    }

    public function testCreateHouseSuccess(): void
    {
        $payload = [
            'sleeping_places' => 3
        ];

        $this->client->request(
            'POST',
            '/api/houses/create',
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
        $this->assertEquals(3, $data['sleeping_places']);

        $house = $this->entityManager->getRepository(House::class)->find($data['id']);
        $this->assertNotNull($house);
        $this->assertEquals(3, $house->getSleepingPlaces());
    }

    public function testCreateHouseMissingParameters(): void
    {
        $payload = [];

        $this->client->request(
            'POST',
            '/api/houses/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testDeleteHouseSuccess(): void
    {
        $house = $this->createTestHouse();
        $houseId = $house->getId();

        $this->client->request('DELETE', "/api/houses/{$houseId}");

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $deletedHouse = $this->entityManager->getRepository(House::class)->find($houseId);
        $this->assertNull($deletedHouse);
    }

    public function testDeleteHouseNotFound(): void
    {
        $this->client->request('DELETE', '/api/houses/9999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
