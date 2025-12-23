<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\Create\BookingCreateDto;
use App\Dto\Output\BookingOutputDto;
use App\Dto\Update\BookingUpdateDto;
use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    paginationEnabled: false,
    output: BookingOutputDto::class,
    operations: [
        new GetCollection(
            routeName: 'get_all_bookings'
        ),
        new GetCollection(
            routeName: 'get_user_bookings'
        ),
        new Post(
            routeName: 'create_booking',
            input: BookingCreateDto::class
        ),
        new Get(
            routeName: 'get_booking_detail'
        ),
        new Delete(
            routeName: 'remove_booking'
        ),
        new Patch(
            routeName: 'update_booking',
            input: BookingUpdateDto::class
        )
    ]
)]
#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
class Booking implements Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: House::class)]
    #[ORM\JoinColumn(name: 'house_id', nullable: false, onDelete: 'CASCADE')]
    private ?House $house = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(name: 'comment')]
    private ?string $comment = null;

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

    #[Override]
    public function __toString(): string
    {
        return "Booking #{$this->id}";
    }
}
