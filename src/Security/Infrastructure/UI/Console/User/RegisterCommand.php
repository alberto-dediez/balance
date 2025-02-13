<?php

namespace App\Security\Infrastructure\UI\Console\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Security\Application\RegisterUser\RegisterUserRequest;
use App\Security\Application\RegisterUser\RegisterUserService;

#[AsCommand(
    name: 'security:user:register',
    description: 'Register a new user in the system'
)]
class RegisterCommand extends Command
{
    public function __construct(private RegisterUserService $registerUser)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = new RegisterUserRequest(
            $input->getArgument('username'), 
            $input->getArgument('password')
        );

        $response = $this->registerUser->__invoke($request);

        $io = new SymfonyStyle($input, $output);
        $io->title('Registro de usuarios');

        $io->table(
            ['Atributo', 'Valor'],
            [
                ['userId', (string) $response->userId],
                ['username', $response->username],
            ]
        );

        return Command::SUCCESS;
    }
}