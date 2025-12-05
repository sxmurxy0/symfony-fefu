<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {
    }

    #[Route('/users', methods: ['GET'])]
    public function getAllUsers(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->json($users, Response::HTTP_OK);
    }

    #[Route('/users/create', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        $requestData = $request->toArray();
        if (!isset($requestData['phone_number'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Missing required param: phone_number!');
        }

        $user = new User($requestData['phone_number']);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, Response::HTTP_OK);
    }

    #[Route('/users/{id}', methods: ['DELETE'])]
    public function deleteUser(User $user): Response
    {
        $this->em->remove($user);
        $this->em->flush();

        return $this->json([], Response::HTTP_OK);
    }
}
