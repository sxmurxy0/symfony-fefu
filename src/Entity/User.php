<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Controller\UserController;
use App\Dto\Create\UserCreateDto;
use App\Dto\Output\UserOutputDto;
use App\Dto\Update\UserUpdateDto;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    routePrefix: '/users',
    paginationEnabled: false,
    output: UserOutputDto::class,
    operations: [
        new GetCollection(
            uriTemplate: '/',
            name: 'get_all_users',
            controller: UserController::class.'::getAllUsers'
        ),
        new Post(
            uriTemplate: '/',
            name: 'create_user',
            input: UserCreateDto::class,
            controller: UserController::class.'::createUser'
        ),
        new Get(
            uriTemplate: '/{id}',
            name: 'get_user_detail',
            controller: UserController::class.'::getUserDetail'
        ),
        new Delete(
            uriTemplate: '/{id}',
            name: 'remove_user',
            controller: UserController::class.'::removeUser'
        ),
        new Patch(
            uriTemplate: '/{id}',
            name: 'update_user',
            input: UserUpdateDto::class,
            controller: UserController::class.'::updateUser'
        )
    ]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[Assert\Length(exactly: 12)]
    #[ORM\Column(name: 'phone_number', length: 12, unique: true)]
    private ?string $phoneNumber = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'password')]
    private ?string $password = null;

    #[ORM\Column(name: 'roles', type: 'json')]
    private array $roles = [];

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->phoneNumber;
    }

    #[Override]
    public function eraseCredentials(): void
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setUser($this);
        }

        return $this;
    }
}
