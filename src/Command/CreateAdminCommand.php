<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new user with role ROLE_ADMIN.',
)]
class CreateAdminCommand
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserService $userService
    ) {
    }

    public function __invoke(
        #[Argument(name: 'phone-numer', description: 'Phone number of the new user.')] string $phoneNumber,
        #[Argument(name: 'password', description: 'Password of the new user.')] string $plainPassword,
        OutputInterface $output
    ): int {
        if ($this->userRepository->existsWithPhoneNumber($phoneNumber)) {
            throw new RuntimeException('User already exists.');
        }

        $user = $this->userService->create($phoneNumber, $plainPassword);
        $user->addRole('ROLE_ADMIN');
        $this->em->flush();

        $output->writeln('User successfully created.');

        return Command::SUCCESS;
    }
}
