<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
class Booking implements JsonSerializable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: House::class)]
    #[ORM\JoinColumn(name: 'house_id', nullable: false, onDelete: 'CASCADE')]
    private ?House $house = null;

    #[ORM\Column(name: 'comment')]
    private ?string $comment = null;

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user?->getId(),
            'house_id' => $this->house?->getId(),
            'comment' => $this->comment
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function setHouse(House $house): static
    {
        $this->house = $house;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
