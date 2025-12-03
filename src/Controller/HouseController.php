<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Create\HouseCreateDto;
use App\Dto\Output\HouseOutputDto;
use App\Dto\Update\HouseUpdateDto;
use App\Repository\HouseRepository;
use App\Service\HouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/houses')]
class HouseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HouseRepository $houseRepository,
        private HouseService $houseService
    ) {
    }

    #[Route(path: '/', name: 'get_all_houses', methods: 'GET')]
    public function getAllHouses(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $houses = $this->houseRepository->findAll();

        return $this->json(HouseOutputDto::mapArray($houses));
    }

    #[Route(path: '/available', name: 'get_available_houses', methods: 'GET')]
    public function getAvailableHouses(): JsonResponse
    {
        $houses = $this->houseRepository->findAvailable();

        return $this->json(HouseOutputDto::mapArray($houses));
    }

    #[Route(path: '/', name: 'create_house', methods: 'POST')]
    public function createHouse(#[MapRequestPayload] HouseCreateDto $dto): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $house = $this->houseService->create($dto->sleepingPlaces);
        $this->em->flush();

        return $this->json(new HouseOutputDto($house), Response::HTTP_CREATED);
    }

    #[Route(path: '/{id}', name: 'get_house_detail', methods: 'GET')]
    public function getHouseDetail(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $house = $this->houseRepository->find($id);
        if (null === $house) {
            throw new NotFoundHttpException('House not found.');
        }

        return $this->json(new HouseOutputDto($house));
    }

    #[Route(path: '/{id}', name: 'remove_house', methods: 'DELETE')]
    public function removeHouse(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $house = $this->houseRepository->find($id);
        if (null === $house) {
            throw new NotFoundHttpException('House not found.');
        }

        $this->houseService->remove($house);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/{id}', name: 'update_house', methods: 'PATCH')]
    public function updateHouse(int $id, #[MapRequestPayload] HouseUpdateDto $dto): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $house = $this->houseRepository->find($id);
        if (null === $house) {
            throw new NotFoundHttpException('House not found.');
        }

        $house->setSleepingPlaces($dto->sleepingPlaces);
        $this->em->flush();

        return $this->json(new HouseOutputDto($house));
    }
}
