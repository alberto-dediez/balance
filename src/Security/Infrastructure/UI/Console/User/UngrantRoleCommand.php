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
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleService;

#[AsCommand(
    name: 'security:user:ungrant-role',
    description: 'Ungrant roles to an user'
)]
class UngrantRoleCommand extends Command
{
    public function __construct(private readonly UngrantRoleService $ungrantRoleService, private readonly UserRepository $userRepository)
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

        if (null === $user) {
            throw new InvalidArgumentException("User not found");
        }

        $role = Role::tryFrom($input->getArgument('role'));

        if (null === $role) {
            throw new InvalidArgumentException("Role not found");
        }

        $request = new UngrantRoleRequest(
            $user->id(),
            $role
        );

        $performBy = UserMother::systemUser();

        $response = $this->ungrantRoleService->__invoke($request, $performBy);

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

    /**
     * @param Role[] $roles
     */
    private function stringifyRoles(array $roles): string
    {
        if (count($roles) === 0) {
            return '';
        }

        $result = [];

        foreach ($roles as $role) {
            $result[] = $role->value;
        }

        return implode(', ', $result);
    }
}
