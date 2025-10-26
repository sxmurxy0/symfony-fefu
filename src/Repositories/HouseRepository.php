<?php

namespace App\Repository;

use App\Service\CSVService;

class HouseRepository {

    const HOUSES_FILE = 'houses.csv', BOOKINGS_FILE = 'bookings.csv';
    const HOUSES_HEADERS = ['id', 'sleeping_places', 'status'];
    const BOOKINGS_HEADERS = ['id', 'house_id', 'phone_number', 'comment'];

    public function __construct(
        private CSVService $csvService
    ) {}

    public function getAvailableHouses(): array {
        $response = [
            'value' => null,
            'error' => null,
            'status' => null
        ];

        try {
            $houses = $this->csvService->readCSVData(self::HOUSES_FILE);
            
            $value = [];
            foreach ($houses as $houseRow) {
                if ($houseRow[2]) 
                    continue;

                $houseRow = array_combine(self::HOUSES_HEADERS, $houseRow);
                unset($houseRow['status']);

                $value[] = $houseRow;
            }
            $response['value'] = $value;
            $response['status'] = 200;
        } catch (\Exception $ex) {
            $response['error'] = (string) $ex;
            $response['status'] = 500;
        }

        return $response;
    }

    public function createBooking(string $houseId, string $phoneNumber, string $comment): array {
        $response = [
            'value' => null,
            'error' => null,
            'status' => null
        ];

        try {
            $houses = $this->csvService->readCSVData(self::HOUSES_FILE);
            $isHouseExistsAndAvailable = false;
            
            foreach ($houses as &$houseRow) {
                if ($houseRow[0] == $houseId and !$houseRow[2]) {
                    $isHouseExistsAndAvailable = true;
                    $houseRow[2] = 1;
                }
            }

            if (!$isHouseExistsAndAvailable) {
                $response['error'] = 'The house doesn\'t exist or is not available now!';
                $response['status'] = 400;
            } else {
                $this->csvService->writeCSVData(self::HOUSES_FILE, $houses);
                $this->csvService->appendCSVData(self::BOOKINGS_FILE, [[uniqid(), $houseId, $phoneNumber, $comment]]);
                $response['status'] = 200;
            }
        } catch (\Exception $ex) {
            $response['error'] = (string) $ex;
            $response['status'] = 500;
        }

        return $response;
    }

    public function editBookingComment(string $id, string $comment): array {
        $response = [
            'value' => null,
            'error' => null
        ];

        try {
            $bookings = $this->csvService->readCSVData(self::BOOKINGS_FILE);
            $isBookingExists = false;

            foreach($bookings as &$bookingRow) {
                if ($bookingRow[0] == $id) {
                    $isBookingExists = true;
                    $bookingRow[3] = $comment;
                }
            }

            if (!$isBookingExists) {
                $response['error'] = 'The booking doesn\'t exist!';
                $response['status'] = 400;
            } else {
                $this->csvService->writeCSVData(self::BOOKINGS_FILE, $bookings);
                $response['status'] = 200;
            }
        } catch (\Exception $ex) {
            $response['error'] = (string) $ex;
            $response['status'] = 500;
        }

        return $response;
    }

}