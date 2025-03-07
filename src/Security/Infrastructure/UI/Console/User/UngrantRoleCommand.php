<?php

namespace App\Security\Infrastructure\UI\Console\User;

use InvalidArgumentException;
use App\Security\Domain\Model\User\Role;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleService;

#[AsCommand(
    name: 'security:user:ungrant-role',
    description: 'Ungrant roles to an user'
)]
class UngrantRoleCommand extends Command
{
    public function __construct(private UngrantRoleService $ungrantRoleService, private UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user')
            ->addArgument('role', InputArgument::REQUIRED, 'The role we want to ungrant')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->userRepository->findByUsername($input->getArgument('username'));

        if (null == $user) {
            throw new InvalidArgumentException("User not found");
        }

        $request = new UngrantRoleRequest(
            $user->id(), 
            Role::tryFrom($input->getArgument('role'))
        );

        $response = $this->ungrantRoleService->__invoke($request);

        $io = new SymfonyStyle($input, $output);
        $io->title('Role eliminado');

        $io->table(
            ['Atributo', 'Valor'],
            [
                ['userId', (string) $response->userId],
                ['username', $response->username],
                ['roles', $this->stringifyRoles($response->roles)],
            ]
        );

        return Command::SUCCESS;
    }

    private function stringifyRoles(array $roles)
    {
        if (count($roles) == 0) {
            return '';
        }

        $result = [];

        foreach ($roles as $role) {
            $result[] = $role->value;
        }

        return implode(', ', $result);
    }
}