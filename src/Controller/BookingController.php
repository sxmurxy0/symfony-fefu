<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/bookings')]
class BookingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookingRepository $bookingRepository,
        private HouseRepository $houseRepository
    ) {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/', methods: ['GET'])]
    public function getUserBookings(#[CurrentUser] User $user): Response
    {
        $bookings = $user->getBookings()->toArray();

        return $this->json($bookings, Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/create', methods: ['POST'])]
    public function createBooking(Request $request, #[CurrentUser] User $user): Response
    {
        $requestData = $request->toArray();
        if (
            !isset($requestData['house_id']) ||
            !isset($requestData['comment'])
        ) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Missing required params: house_id or comment!');
        }

        $houseId = $requestData['house_id'];
        $house = $this->houseRepository->find($houseId);

        if (!$house) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'House with specified house_id not found!');
        }

        if (!$this->houseRepository->isAvailable($houseId)) {
            throw new HttpException(Response::HTTP_CONFLICT, 'House with specified house_id is not available now!');
        }

        $booking = $this->bookingRepository->create($user, $house, $requestData['comment']);

        return $this->json($booking, Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/comment', methods: ['PATCH'])]
    public function editBookingComment(Request $request, Booking $booking, #[CurrentUser] User $user): Response
    {
        $requestData = $request->toArray();
        if (!isset($requestData['comment'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Missing required param: comment!');
        }

        if (!$user->getBookings()->contains($booking)) {
            throw new HttpException(Response::HTTP_CONFLICT, "You don't have access to change booking!");
        }

        $booking->setComment($requestData['comment']);
        $this->em->flush();

        return $this->json([], Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', methods: ['DELETE'])]
    public function removeBooking(Booking $booking, #[CurrentUser] User $user): Response
    {
        if (!$user->getBookings()->contains($booking)) {
            throw new HttpException(Response::HTTP_CONFLICT, "You don't have access to remove booking!");
        }

        $this->bookingRepository->remove($booking);

        return $this->json([], Response::HTTP_OK);
    }
}
