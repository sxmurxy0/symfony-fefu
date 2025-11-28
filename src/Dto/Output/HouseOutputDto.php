<?php

declare(strict_types=1);

namespace App\Dto\Output;

use App\Entity\House;

class HouseOutputDto
{
    public int $id;

    public int $sleepingPlaces;

    public function __construct(House $house)
    {
        $this->id = $house->getId();
        $this->sleepingPlaces = $house->getSleepingPlaces();
    }

    public static function mapArray(array $houses): array
    {
        return array_map(
            fn ($house) => new HouseOutputDto($house),
            $houses
        );
    }
}
