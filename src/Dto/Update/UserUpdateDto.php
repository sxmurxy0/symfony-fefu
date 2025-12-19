<?php

declare(strict_types=1);

namespace App\Dto\Update;

use Symfony\Component\Validator\Constraints as Assert;

class UserUpdateDto
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    public ?string $password = null;
}
