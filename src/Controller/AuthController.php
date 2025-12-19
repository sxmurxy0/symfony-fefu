<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\AccessTokenRepository;
use App\Repository\UserRepository;
use App\Service\AccessTokenService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function login(Request $request): Response
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

        $user = $this->userRepository->findOneByPhoneNumber($phoneNumber);

        if (!$user) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'User with specified phone_number not found!');
        }

        if (!$this->userService->isPasswordValid($user, $plainPassword)) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Wrong password!');
        }

        $this->accessTokenService->removeByUser($user);

        $this->em->flush();

        $accessToken = $this->accessTokenService->create($user);

        $this->em->flush();

        return $this->json($accessToken, Response::HTTP_OK);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/logout', methods: ['POST'])]
    public function logout(#[CurrentUser] User $user): Response
    {
        $this->accessTokenService->removeByUser($user);

        $this->em->flush();

        return $this->json([], Response::HTTP_OK);
    }
}
