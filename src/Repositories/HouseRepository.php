<?php

namespace App\Repository;

use App\Service\CSVService;

class HouseRepository {

    const HOUSES_FILE = 'houses.csv';
    const HOUSES_HEADERS = ['id', 'sleeping_places', 'status'];
    const BOOKINGS_FILE = 'bookings.csv';
    const BOOKINGS_HEADERS = ['id', 'house_id', 'phone_number', 'comment'];

    public function __construct(
        private CSVService $csvService
    ) {}

    public function getAvailableHouses(): array {
        $houses = $this->csvService->readCSVData(self::HOUSES_FILE);
            
        $result = [];
        foreach ($houses as $houseRow) {
            $isBooked = $houseRow[2];
            if ($isBooked)
                continue;

            $houseRow = array_combine(self::HOUSES_HEADERS, $houseRow);
            unset($houseRow['status']);

            $result[] = $houseRow;
        }

        return $result;
    }

    public function createBooking(string $houseId, string $phoneNumber, string $comment): void {
        $houses = $this->csvService->readCSVData(self::HOUSES_FILE);
        $isHouseExistsAndAvailable = false;
        
        foreach ($houses as &$houseRow) {
            $id = $houseRow[0];
            $isBooked = &$houseRow[2];
            if ($id == $houseId && !$isBooked) {
                $isHouseExistsAndAvailable = true;
                $isBooked = 1;
            }
        }

        if (!$isHouseExistsAndAvailable) {
            throw new \RuntimeException("The house with id $houseId doesn't exist or is not available now!");
        } else {
            $this->csvService->writeCSVData(self::HOUSES_FILE, $houses);
            $this->csvService->appendCSVData(self::BOOKINGS_FILE, [[uniqid(), $houseId, $phoneNumber, $comment]]);
        }
    }

    public function editBookingComment(string $bookingId, string $newComment): void {
        $bookings = $this->csvService->readCSVData(self::BOOKINGS_FILE);
        $isBookingExists = false;

        foreach($bookings as &$bookingRow) {
            $id = $bookingRow[0];
            $comment = &$bookingRow[3];
            if ($id == $bookingId) {
                $isBookingExists = true;
                $comment = $newComment;
            }
        }

        if (!$isBookingExists) {
            throw new \RuntimeException("The booking with id $id doesn't exist!");
        } else {
            $this->csvService->writeCSVData(self::BOOKINGS_FILE, $bookings);
        }
    }

}