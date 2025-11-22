<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
        parent::__construct($registry, Booking::class);
    }

    public function create(User $user, House $house, string $comment): Booking
    {
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setHouse($house);
        $booking->setComment($comment);

        $user->addBooking($booking);

        $this->getEntityManager()->persist($booking);
        $this->getEntityManager()->flush();

        return $booking;
    }

    public function remove(?Booking $booking): void
    {
        if ($booking) {
            $this->getEntityManager()->remove($booking);
            $this->getEntityManager()->flush();
        }
    }

    public function removeById(int $id): void
    {
        $booking = $this->find($id);
        $this->remove($booking);
    }
}
