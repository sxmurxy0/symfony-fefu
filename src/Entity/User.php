<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JsonSerializable;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements JsonSerializable {
    
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'phone_number')]
    private string $phoneNumber;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $bookings;

    public function __construct(string $phoneNumber) {
        $this->phoneNumber = $phoneNumber;
        $this->bookings = new ArrayCollection();
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'phone_number' => $this->phoneNumber,
            'bookings_count' => $this->bookings->count(),
        ];
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getPhoneNumber(): string {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getBookings(): Collection {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setUser($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getUser() === $this) {
                $booking->setUser(null);
            }
        }

        return $this;
    }

}