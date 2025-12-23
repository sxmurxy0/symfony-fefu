<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Create\UserCreateDto;
use App\Dto\Output\BookingOutputDto;
use App\Dto\Output\UserOutputDto;
use App\Dto\Update\UserUpdateDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserService $userService
    ) {
    }

    #[Route(path: '/', name: 'get_all_users', methods: 'GET')]
    public function getAllUsers(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $users = $this->userRepository->findAll();

        return $this->json(UserOutputDto::mapArray($users));
    }

    #[Route(path: '/', name: 'create_user', methods: 'POST')]
    public function createUser(#[MapRequestPayload] UserCreateDto $dto): JsonResponse
    {
        if ($this->userRepository->existsWithPhoneNumber($dto->phoneNumber)) {
            throw new ConflictHttpException('User already exists.');
        }

        $user = $this->userService->create($dto->phoneNumber, $dto->password);
        $this->em->flush();

        return $this->json(new UserOutputDto($user), Response::HTTP_CREATED);
    }

    #[Route(path: '/{id}/bookings', name: 'get_user_bookings', methods: 'GET')]
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

    #[Route(path: '/{id}', name: 'get_user_detail', methods: 'GET')]
    public function getUserDetail(int $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $id) {
            throw new AccessDeniedHttpException('You can only view your own profile.');
        }

        return $this->json(new UserOutputDto($user));
    }

    #[Route(path: '/{id}', name: 'remove_user', methods: 'DELETE')]
    public function removeUser(int $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $id) {
            throw new AccessDeniedHttpException('You can only delete your own account.');
        }

        $this->userService->remove($user);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/{id}', name: 'update_user', methods: 'PATCH')]
    public function updateUser(
        int $id,
        #[MapRequestPayload] UserUpdateDto $dto,
        #[CurrentUser] User $currentUser
    ): JsonResponse {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $id) {
            throw new AccessDeniedHttpException('You can only update your own profile.');
        }

        $user->setPlainPassword($dto->password);
        $this->userService->updatePassword($user);
        $this->em->flush();

        return $this->json(new UserOutputDto($user));
    }
}
