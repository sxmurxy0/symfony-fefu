<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\House;
use App\Repository\HouseRepository;
use App\Service\HouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/houses')]
class HouseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HouseRepository $houseRepository,
        private HouseService $houseService
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', methods: ['GET'])]
    public function getAllHouses(): Response
    {
        $houses = $this->houseRepository->findAll();

        return $this->json($houses, Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/available', methods: ['GET'])]
    public function getAvailableHouses(): Response
    {
        $houses = $this->houseRepository->findAvailable();

        return $this->json($houses, Response::HTTP_OK);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/create', methods: ['POST'])]
    public function createHouse(Request $request): Response
    {
        $requestData = $request->toArray();

        if (!isset($requestData['sleeping_places'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Missing required param: sleeping_places!');
        }

        $sleepingPlaces = $requestData['sleeping_places'];

        $house = $this->houseService->create($sleepingPlaces);

        $this->em->flush();

        return $this->json($house, Response::HTTP_OK);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', methods: ['DELETE'])]
    public function removeHouse(House $house): Response
    {
        $this->houseService->remove($house);

        $this->em->flush();

        return $this->json([], Response::HTTP_OK);
    }
}
