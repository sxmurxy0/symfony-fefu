<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Create\BookingCreateDto;
use App\Dto\Output\BookingOutputDto;
use App\Dto\Update\BookingUpdateDto;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Repository\UserRepository;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/bookings')]
class BookingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookingRepository $bookingRepository,
        private BookingService $bookingService,
        private UserRepository $userRepository,
        private HouseRepository $houseRepository
    ) {
    }

    #[Route(path: '/', name: 'get_all_bookings', methods: 'GET')]
    public function getAllBookings(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $bookings = $this->bookingRepository->findAll();

        return $this->json(BookingOutputDto::mapArray($bookings));
    }

    #[Route(path: '/', name: 'create_booking', methods: 'POST')]
    public function createBooking(
        #[MapRequestPayload] BookingCreateDto $dto,
        #[CurrentUser] User $currentUser
    ): JsonResponse {
        $user = $this->userRepository->find($dto->userId);
        if (null === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() != $dto->userId) {
            throw new AccessDeniedHttpException('You can only create bookings for yourself.');
        }

        $house = $this->houseRepository->find($dto->houseId);
        if (null === $house) {
            throw new NotFoundHttpException('House not found.');
        }

        if (!$this->houseRepository->isAvailable($house->getId())) {
            throw new ConflictHttpException('House is not available now.');
        }

        $booking = $this->bookingService->create($user, $house, $dto->comment);
        $this->em->flush();

        return $this->json(new BookingOutputDto($booking), Response::HTTP_CREATED);
    }

    #[Route(path: '/{id}', name: 'get_booking_detail', methods: 'GET')]
    public function getBookingDetail(int $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        if (
            !$this->isGranted('ROLE_ADMIN') &&
            $booking->getUser()->getId() != $currentUser->getId()
        ) {
            throw new AccessDeniedHttpException('You can only view your own bookings.');
        }

        return $this->json(new BookingOutputDto($booking));
    }

    #[Route(path: '/{id}', name: 'remove_booking', methods: 'DELETE')]
    public function removeBooking(int $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        if (
            !$this->isGranted('ROLE_ADMIN') &&
            $booking->getUser()->getId() != $currentUser->getId()
        ) {
            throw new AccessDeniedHttpException('You can only delete your own bookings.');
        }

        $this->bookingService->remove($booking);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/{id}', name: 'update_booking', methods: 'PATCH')]
    public function updateBooking(
        int $id,
        #[MapRequestPayload] BookingUpdateDto $dto,
        #[CurrentUser] User $currentUser
    ): JsonResponse {
        $booking = $this->bookingRepository->find($id);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        if (
            !$this->isGranted('ROLE_ADMIN') &&
            $booking->getUser()->getId() != $currentUser->getId()
        ) {
            throw new AccessDeniedHttpException('You can only update your own bookings.');
        }

        $booking->setComment($dto->comment);
        $this->em->flush();

        return $this->json(new BookingOutputDto($booking));
    }
}
