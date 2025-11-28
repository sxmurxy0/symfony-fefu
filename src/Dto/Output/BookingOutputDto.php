<?php

declare(strict_types=1);

namespace App\Dto\Output;

use App\Entity\Booking;

class BookingOutputDto
{
    public int $id;

    public int $userId;

    public int $houseId;

    public string $comment;

    public function __construct(Booking $booking)
    {
        $this->id = $booking->getId();
        $this->userId = $booking->getUser()->getId();
        $this->houseId = $booking->getHouse()->getId();
        $this->comment = $booking->getComment();
    }

    public static function mapArray(array $bookings): array
    {
        return array_map(
            fn ($booking) => new BookingOutputDto($booking),
            $bookings
        );
    }
}
