<?php

declare(strict_types=1);

namespace App\Dto\Update;

use Symfony\Component\Validator\Constraints as Assert;

class HouseUpdateDto
{
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 24)]
    public ?int $sleepingPlaces = null;
}
