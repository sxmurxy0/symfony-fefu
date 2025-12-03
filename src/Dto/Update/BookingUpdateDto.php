<?php

declare(strict_types=1);

namespace App\Dto\Update;

use Symfony\Component\Validator\Constraints as Assert;

class BookingUpdateDto
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $comment = null;
}
