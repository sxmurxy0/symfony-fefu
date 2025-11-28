<?php

declare(strict_types=1);

namespace App\Dto\Create;

use Symfony\Component\Validator\Constraints as Assert;

class BookingCreateDto
{
    #[Assert\NotNull]
    public ?int $userId = null;

    #[Assert\NotNull]
    public ?int $houseId = null;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $comment = null;
}
