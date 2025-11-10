<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HouseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, House::class);
    }

    public function findAvailable(): array
    {
        $bookedHousesId = $this->getEntityManager()->createQuery(
            '
            SELECT 
                IDENTITY(booking.house)
            FROM App\Entity\Booking AS booking
            '
        )->getArrayResult();

        $bookedHousesId = array_column($bookedHousesId, 1);

        if (!$bookedHousesId) {
            return $this->findAll();
        }

        $query = $this->getEntityManager()->createQuery(
            '
            SELECT
                house
            FROM App\Entity\House AS house
            WHERE house.id NOT IN (:booked_houses)
            '
        )->setParameter('booked_houses', $bookedHousesId);

        return $query->getResult();
    }

    public function isHouseAvailable(int $houseId): bool
    {
        $query = $this->getEntityManager()->createQuery(
            '
            SELECT
                COUNT(booking.id)
            FROM App\Entity\Booking AS booking
            WHERE booking.house = :house_id
            '
        )->setParameter('house_id', $houseId);

        return 0 == $query->getSingleScalarResult();
    }
}
