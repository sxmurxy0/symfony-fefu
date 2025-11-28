<?php

declare(strict_types=1);

namespace App\Dto\Output;

use App\Entity\AccessToken;
use DateTimeImmutable;

class AccessTokenOutputDto
{
    public int $id;

    public string $value;

    public int $userId;

    public DateTimeImmutable $createdAt;

    public bool $isExpired;

    public function __construct(AccessToken $accessToken)
    {
        $this->id = $accessToken->getId();
        $this->value = $accessToken->getValue();
        $this->userId = $accessToken->getUser()->getId();
        $this->createdAt = $accessToken->getCreatedAt();
        $this->isExpired = $accessToken->isExpired();
    }
}
