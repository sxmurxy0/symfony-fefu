<?php

declare(strict_types=1);

namespace App\Dto\Create;

use Symfony\Component\Validator\Constraints as Assert;

class UserCreateDto
{
    #[Assert\NotNull]
    #[Assert\Length(exactly: 12)]
    public ?string $phoneNumber = null;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $password = null;
}
