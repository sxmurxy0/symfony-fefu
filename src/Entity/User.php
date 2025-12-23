<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\Create\UserCreateDto;
use App\Dto\Output\UserOutputDto;
use App\Dto\Update\UserUpdateDto;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    paginationEnabled: false,
    output: UserOutputDto::class,
    operations: [
        new GetCollection(
            routeName: 'get_all_users'
        ),
        new Post(
            routeName: 'create_user',
            input: UserCreateDto::class
        ),
        new Get(
            routeName: 'get_user_detail'
        ),
        new Delete(
            routeName: 'remove_user'
        ),
        new Patch(
            routeName: 'update_user',
            input: UserUpdateDto::class
        )
    ]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements Stringable, UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[Assert\Length(exactly: 12)]
    #[ORM\Column(name: 'phone_number', length: 12, unique: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: 'password')]
    private ?string $password = null;

    private ?string $plainPassword = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'roles', type: 'json')]
    private array $roles = ['ROLE_USER'];

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
        $this->plainPassword = null;
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

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    #[Override]
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

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

    #[Override]
    public function __toString(): string
    {
        return "User #{$this->id}";
    }
}
