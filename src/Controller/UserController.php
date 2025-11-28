<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Create\UserCreateDto;
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
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserService $userService
    ) {
    }

    public function getAllUsers(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $users = $this->userRepository->findAll();

        return $this->json(UserOutputDto::mapArray($users));
    }

    public function createUser(#[MapRequestPayload] UserCreateDto $dto): JsonResponse
    {
        if ($this->userRepository->existsWithPhoneNumber($dto->phoneNumber)) {
            throw new ConflictHttpException('User already exists.');
        }

        $user = $this->userService->create($dto->phoneNumber, $dto->password);
        $this->em->flush();

        return $this->json(new UserOutputDto($user), Response::HTTP_CREATED);
    }

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

        $this->userService->updatePassword($user, $dto->password);
        $this->em->flush();

        return $this->json(new UserOutputDto($user));
    }
}
