<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, JsonSerializable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'phone_number', unique: true)]
    private ?string $phoneNumber = null;

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

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'phone_number' => $this->phoneNumber,
            'bookings_count' => $this->bookings->count(),
        ];
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
