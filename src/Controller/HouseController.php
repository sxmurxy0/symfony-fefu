<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\HouseRepository;

class HouseController extends AbstractController {

    public function __construct(
        private HouseRepository $houseRepository
    ) {}

    #[Route('/', name: 'home')]
    public function home(): Response {
        return new Response("Hello world!");
    }

    #[Route('/api/houses', methods: ['GET'])]
    public function getAvailableHouses(): Response {
        $response = $this->houseRepository->getAvailableHouses();
        if ($response['status'] !== 200) {
            return new JsonResponse(['Error' => $response['error']], $response['status']);
        }

        return new JsonResponse($response['value'], 200);
    }

    #[Route('/api/booking', methods: ['POST'])]
    public function createBooking(Request $request): Response {
        $requestData = json_decode($request->getContent(), true);
        if (!isset($requestData['houseId']) or !isset($requestData['phoneNumber'])) {
            return new JsonResponse(['Error' => 'Missing required params: houseId or phoneNumber!'], 400);
        }
        
        $response = $this->houseRepository->createBooking(
            $requestData['houseId'], 
            $requestData['phoneNumber'], 
            $requestData['comment'] ?? ''
        );
        if ($response['status'] !== 200) {
            return new JsonResponse(['Error' => $response['error']], $response['status']);
        }

        return new JsonResponse(['Comment' => 'The booking was successfully created!'], 200);
    }

    #[Route('/api/booking/{id}', methods: ['PATCH'])]
    public function editBookingComment(string $id, Request $request): Response {
        $requestData = json_decode($request->getContent(), true);
        if (!isset($requestData['comment'])) {
            return new JsonResponse(['Error' => 'Missing required param: comment!'], 400);
        }

        $response = $this->houseRepository->editBookingComment(
            $id,
            $requestData['comment']
        );
        if ($response['status'] !== 200) {
            return new JsonResponse(['Error' => $response['error']], $response['status']);
        }

        return new JsonResponse(['Comment' => 'The booking comment was successfully edited!'], 200);
    }

}