<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccessTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
#[ORM\Table(name: 'access_tokens')]
class AccessToken implements Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'value', length: 255, unique: true)]
    private ?string $value = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'expires_at')]
    private ?DateTimeImmutable $expiresAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    #[ORM\PrePersist]
    public function generateValue(): void
    {
        if (null === $this->value) {
            $this->value = bin2hex(random_bytes(32));
        }
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    #[Override]
    public function __toString(): string
    {
        return "AccessToken #{$this->id}";
    }
}
