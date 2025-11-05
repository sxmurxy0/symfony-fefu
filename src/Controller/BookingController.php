<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BookingRepository;
use App\Entity\{Booking, User, House};

class BookingController extends AbstractController {

    public function __construct(
        private EntityManagerInterface $em,
        private BookingRepository $bookingRepository
    ) {}

    #[Route('/bookings', methods: ['GET'])]
    public function getAllBokings(): Response {
        $bookings = $this->bookingRepository->findAll();

        return $this->json($bookings, Response::HTTP_OK);
    }

    #[Route('/bookings/create', methods: ['POST'])]
    public function createBooking(Request $request): Response {
        $requestData = $request->toArray();
        if (!isset($requestData['user_id']) || !isset($requestData['house_id'])
                || !isset($requestData['comment'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 
                'Missing required params: user_id, house_id or comment!');
        }
        
        $userId = $requestData['user_id'];
        $houseId = $requestData['house_id'];

        $userRepository = $this->em->getRepository(User::class);
        $houseRepositotry = $this->em->getRepository(House::class);
        
        $user = $userRepository->find($userId);
        $house = $houseRepositotry->find($houseId);
        if (!$user || !$house) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 
                "User $userId or house $houseId not found!");
        }

        if (!$houseRepositotry->isHouseAvailable($houseId)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 
                "House $houseId is not available now!");
        }

        $booking = new Booking($user, $house, $requestData['comment']);
        $this->em->persist($booking);
        $this->em->flush();

        return $this->json($booking, Response::HTTP_OK);
    }

    #[Route('/bookings/{id}/comment', methods: ['PATCH'])]
    public function editBookingComment(Request $request, Booking $booking): Response {
        $requestData = $request->toArray();
        if (!isset($requestData['comment'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 
                'Missing required param: comment!');
        }

        $booking->setComment($requestData['comment']);
        $this->em->flush();

        return $this->json([], Response::HTTP_OK);
    }

    #[Route('/bookings/{id}', methods: ['DELETE'])]
    public function deleteBooking(Booking $booking): Response {
        $this->em->remove($booking);
        $this->em->flush();
        
        return $this->json([], Response::HTTP_OK);
    }

}