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

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $requestData = $request->toArray();

        if (
            !isset($requestData['phone_number']) ||
            !isset($requestData['password'])
        ) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Missing required params: phone_number or password!');
        }

        $phoneNumber = $requestData['phone_number'];
        $plainPassword = $requestData['password'];

        if ($this->userRepository->existsWithPhoneNumber($phoneNumber)) {
            throw new HttpException(Response::HTTP_CONFLICT, 'User with specified phone_number already exists!');
        }

        $user = $this->userService->create($phoneNumber, $plainPassword);

        $accessToken = $this->accessTokenService->create($user);

        $this->em->flush();

        return $this->json($accessToken, Response::HTTP_OK);
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
