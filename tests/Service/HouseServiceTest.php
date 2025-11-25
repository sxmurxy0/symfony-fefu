<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\House;
use App\Repository\HouseRepository;
use App\Service\HouseService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class HouseServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private HouseRepository $repository;
    private HouseService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(HouseRepository::class);
        $this->service = new HouseService($this->entityManager, $this->repository);
    }

    public function testCreate(): void
    {
        $sleepingPlaces = 3;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(House::class));

        $house = $this->service->create($sleepingPlaces);

        $this->assertInstanceOf(House::class, $house);
        $this->assertSame($sleepingPlaces, $house->getSleepingPlaces());
    }

    public function testRemoveWithHouse(): void
    {
        $house = new House();
        $house->setSleepingPlaces(2);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($house);

        $this->service->remove($house);
    }

    public function testRemoveWithNull(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->service->remove(null);
    }

    public function testRemoveByIdWhenHouseExists(): void
    {
        $house = new House();
        $house->setSleepingPlaces(2);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($house);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($house);

        $this->service->removeById(1);
    }

    public function testRemoveByIdWhenHouseDoesNotExist(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->service->removeById(999);
    }
}
