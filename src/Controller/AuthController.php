<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginDto;
use App\Dto\Output\AccessTokenOutputDto;
use App\Entity\User;
use App\Repository\AccessTokenRepository;
use App\Repository\UserRepository;
use App\Service\AccessTokenService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserService $userService,
        private AccessTokenRepository $accessTokenRepository,
        private AccessTokenService $accessTokenService
    ) {
    }

    #[Route('/login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginDto $dto): JsonResponse
    {
        $user = $this->userRepository->findOneByPhoneNumber($dto->phoneNumber);
        if (null === $user) {
            throw new AuthenticationException('User not found.');
        }

        if (!$this->userService->isPasswordValid($user, $dto->password)) {
            throw new AuthenticationException('Invalid password.');
        }

        $this->accessTokenService->removeByUser($user);
        $this->em->flush();

        $accessToken = $this->accessTokenService->create($user);
        $this->em->flush();

        return $this->json(new AccessTokenOutputDto($accessToken));
    }

    #[Route('/logout', methods: ['POST'])]
    public function logout(#[CurrentUser] User $currentUser): JsonResponse
    {
        $this->accessTokenService->removeByUser($currentUser);
        $this->em->flush();

        return new JsonResponse();
    }
}
