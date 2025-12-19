<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookingRepository $repository
    ) {
    }

    public function create(User $user, House $house, string $comment): Booking
    {
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setHouse($house);
        $booking->setComment($comment);

        $user->addBooking($booking);

        $this->em->persist($booking);

        return $booking;
    }

    public function remove(?Booking $booking): void
    {
        if (null !== $booking) {
            $this->em->remove($booking);
        }
    }

    public function removeById(int $id): void
    {
        $booking = $this->repository->find($id);
        $this->remove($booking);
    }
}
