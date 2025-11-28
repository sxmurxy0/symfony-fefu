<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Controller\BookingController;
use App\Dto\Create\BookingCreateDto;
use App\Dto\Output\BookingOutputDto;
use App\Dto\Update\BookingUpdateDto;
use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    routePrefix: '/bookings',
    paginationEnabled: false,
    output: BookingOutputDto::class,
    operations: [
        new GetCollection(
            uriTemplate: '/',
            name: 'get_all_bookings',
            controller: BookingController::class.'::getAllBookings'
        ),
        new GetCollection(
            routePrefix: '/users',
            uriTemplate: '/{id}/bookings',
            name: 'get_user_bookings',
            controller: BookingController::class.'::getUserBookings'
        ),
        new Post(
            uriTemplate: '/',
            name: 'create_booking',
            input: BookingCreateDto::class,
            controller: BookingController::class.'::createBooking'
        ),
        new Get(
            uriTemplate: '/{id}',
            name: 'get_booking_detail',
            controller: BookingController::class.'::getBookingDetail'
        ),
        new Delete(
            uriTemplate: '/{id}',
            name: 'remove_booking',
            controller: BookingController::class.'::removeBooking'
        ),
        new Patch(
            uriTemplate: '/{id}',
            name: 'update_booking',
            input: BookingUpdateDto::class,
            controller: BookingController::class.'::updateBooking'
        )
    ]
)]
#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
class Booking
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
}
