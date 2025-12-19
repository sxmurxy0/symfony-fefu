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
use Symfony\Component\Security\Http\Attribute\CurrentUser;

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

    public function getAllBookings(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $bookings = $this->bookingRepository->findAll();

        return $this->json(BookingOutputDto::mapArray($bookings));
    }

    public function getUserBookings(int $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() != $id) {
            throw new AccessDeniedHttpException('You can only view your own bookings.');
        }

        $bookings = $user->getBookings()->toArray();

        return $this->json(BookingOutputDto::mapArray($bookings));
    }

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
