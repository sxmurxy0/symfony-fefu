<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\House;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;

class HouseService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HouseRepository $repository
    ) {
    }

    public function create(int $sleepingPlaces): House
    {
        $house = new House();
        $house->setSleepingPlaces($sleepingPlaces);

        $this->em->persist($house);

        return $house;
    }

    public function remove(?House $house): void
    {
        if ($house) {
            $this->em->remove($house);
        }
    }

    public function removeById(int $id): void
    {
        $house = $this->repository->find($id);
        $this->remove($house);
    }
}
