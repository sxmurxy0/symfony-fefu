<?php

declare(strict_types=1);

namespace App\Dto\Output;

use App\Entity\User;

class UserOutputDto
{
    public int $id;

    public string $phoneNumber;

    public int $bookingsCount;

    public function __construct(User $user)
    {
        $this->id = $user->getId();
        $this->phoneNumber = $user->getPhoneNumber();
        $this->bookingsCount = count($user->getBookings());
    }

    public static function mapArray(array $users): array
    {
        return array_map(
            fn ($user) => new UserOutputDto($user),
            $users
        );
    }
}
