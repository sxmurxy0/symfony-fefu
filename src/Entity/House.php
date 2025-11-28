<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Controller\HouseController;
use App\Dto\Create\HouseCreateDto;
use App\Dto\Output\HouseOutputDto;
use App\Dto\Update\HouseUpdateDto;
use App\Repository\HouseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    routePrefix: '/houses',
    paginationEnabled: false,
    output: HouseOutputDto::class,
    operations: [
        new GetCollection(
            uriTemplate: '/',
            name: 'get_all_houses',
            controller: HouseController::class.'::getAllHouses'
        ),
        new GetCollection(
            uriTemplate: '/available',
            name: 'get_available_houses',
            controller: HouseController::class.'::getAvailableHouses'
        ),
        new Post(
            uriTemplate: '/',
            name: 'create_house',
            input: HouseCreateDto::class,
            controller: HouseController::class.'::createHouse'
        ),
        new Get(
            uriTemplate: '/{id}',
            name: 'get_house_detail',
            controller: HouseController::class.'::getHouseDetail'
        ),
        new Delete(
            uriTemplate: '/{id}',
            name: 'remove_house',
            controller: HouseController::class.'::removeHouse'
        ),
        new Patch(
            uriTemplate: '/{id}',
            name: 'update_house',
            input: HouseUpdateDto::class,
            controller: HouseController::class.'::updateHouse'
        )
    ]
)]
#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\Table(name: 'houses')]
class House
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
}
