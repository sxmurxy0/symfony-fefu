<?php

declare(strict_types=1);

namespace App\Dto\Create;

use Symfony\Component\Validator\Constraints as Assert;

class HouseCreateDto
{
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 24)]
    public ?int $sleepingPlaces = null;
}
