<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\Create\HouseCreateDto;
use App\Dto\Output\HouseOutputDto;
use App\Dto\Update\HouseUpdateDto;
use App\Repository\HouseRepository;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    paginationEnabled: false,
    output: HouseOutputDto::class,
    operations: [
        new GetCollection(
            routeName: 'get_all_houses'
        ),
        new GetCollection(
            routeName: 'get_available_houses'
        ),
        new Post(
            routeName: 'create_house',
            input: HouseCreateDto::class
        ),
        new Get(
            routeName: 'get_house_detail'
        ),
        new Delete(
            routeName: 'remove_house'
        ),
        new Patch(
            routeName: 'update_house',
            input: HouseUpdateDto::class
        )
    ]
)]
#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\Table(name: 'houses')]
class House implements Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(name: 'id')]
    private ?int $id = null;

    #[Assert\Range(min: 0, max: 24)]
    #[ORM\Column(name: 'sleeping_places')]
    private ?int $sleepingPlaces = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSleepingPlaces(): ?int
    {
        return $this->sleepingPlaces;
    }

    public function setSleepingPlaces(int $sleepingPlaces): static
    {
        $this->sleepingPlaces = $sleepingPlaces;

        return $this;
    }

    #[Override]
    public function __toString(): string
    {
        return "House #{$this->id}";
    }
}
