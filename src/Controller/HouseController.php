<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\House;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

class HouseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HouseRepository $houseRepository
    ) {
    }

    #[Route('/houses', methods: ['GET'])]
    public function getAllHouses(): Response
    {
        $houses = $this->houseRepository->findAll();

        return $this->json($houses, Response::HTTP_OK);
    }

    #[Route('/houses/available', methods: ['GET'])]
    public function getAvailableHouses(): Response
    {
        $houses = $this->houseRepository->findAvailable();

        return $this->json($houses, Response::HTTP_OK);
    }

    #[Route('/houses/create', methods: ['POST'])]
    public function createHouse(Request $request): Response
    {
        $requestData = $request->toArray();
        if (!isset($requestData['sleeping_places'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Missing required param: sleeping_places!');
        }

        $house = new House($requestData['sleeping_places']);
        $this->em->persist($house);
        $this->em->flush();

        return $this->json($house, Response::HTTP_OK);
    }

    #[Route('/houses/{id}', methods: ['DELETE'])]
    public function deleteHouse(House $house): Response
    {
        $this->em->remove($house);
        $this->em->flush();

        return $this->json([], Response::HTTP_OK);
    }
}
