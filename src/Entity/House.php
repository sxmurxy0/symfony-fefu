<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HouseRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\Table(name: 'houses')]
class House implements JsonSerializable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'sleeping_places')]
    private int $sleepingPlaces;

    public function __construct(int $sleepingPlaces)
    {
        $this->sleepingPlaces = $sleepingPlaces;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'sleeping_places' => $this->sleepingPlaces
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSleepingPlaces(): int
    {
        return $this->sleepingPlaces;
    }

    public function setSleepingPlaces(int $sleepingPlaces): static
    {
        $this->sleepingPlaces = $sleepingPlaces;

        return $this;
    }
}
