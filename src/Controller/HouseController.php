<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\HouseRepository;
use Symfony\Component\Filesystem\Exception\IOException;

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
        try {
            $houses = $this->houseRepository->getAvailableHouses();
        } catch (\RuntimeException $ex) {
            throw new HttpException(500, $ex->getMessage());
        }

        return new JsonResponse($houses, 200);
    }

    #[Route('/api/booking', methods: ['POST'])]
    public function createBooking(Request $request): Response {
        $requestData = json_decode($request->getContent(), true);
        if (!isset($requestData['houseId']) || !isset($requestData['phoneNumber'])) {
            throw new HttpException(400, 'Missing required params: houseId or phoneNumber!');
        }
        
        try {
            $this->houseRepository->createBooking(
                $requestData['houseId'], 
                $requestData['phoneNumber'], 
                $requestData['comment'] ?? ''
            );
        } catch (IOException $ex) {
            throw new HttpException(500, $ex->getMessage());
        } catch (\RuntimeException $ex) {
            throw new HttpException(400, $ex->getMessage());
        }
    
        return new JsonResponse(['status' => true], 200);
    }

    #[Route('/api/booking/{id}', methods: ['PATCH'])]
    public function editBookingComment(string $id, Request $request): Response {
        $requestData = json_decode($request->getContent(), true);
        if (!isset($requestData['comment'])) {
            throw new HttpException(400, 'Missing required param: comment!');
        }

        try {
            $this->houseRepository->editBookingComment(
                $id,
                $requestData['comment']
            );
        } catch (IOException $ex) {
            throw new HttpException(500, $ex->getMessage());
        } catch (\RuntimeException $ex) {
            throw new HttpException(400, $ex->getMessage());
        }

        return new JsonResponse(['status' => true], 200);
    }

}