<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Console\Account;

use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'security:account:register',
    description: 'Register a new user in the system',
)]
class RegisterCommand extends Command
{
    public function __construct(private readonly RegisterAccountService $registerAccount)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the account')
            ->addArgument('active', InputArgument::REQUIRED, 'The state of the account')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = new RegisterAccountRequest(
            name: $input->getArgument('name'),
            active: 'true' == $input->getArgument('active'),
        );

        $user = UserMother::systemUser();

        $response = $this->registerAccount->__invoke($request, $user);

        $io = new SymfonyStyle($input, $output);
        $io->title('Registro de cuentas');

        $io->table(
            ['Atributo', 'Valor'],
            [
                ['accountId', (string) $response->accountId],
                ['name', $response->name],
                ['estado', $response->active ? 'Activa' : 'Inactiva'],
            ],
        );

        return Command::SUCCESS;
    }
}
