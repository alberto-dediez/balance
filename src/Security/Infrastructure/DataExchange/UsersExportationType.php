<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\DataExchange;

use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Application\User\Export\UsersExportFilter;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Form\AdminUsersExportType;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class UsersExportationType implements ExportationType
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AccountRepository $accountRepository,
        private readonly UserAuthorizer $authorizer,
        private readonly TranslatorInterface $translator,
        private readonly ?CurrentAccountIdProvider $currentAccountIdProvider = null,
    ) {}

    public function code(): string
    {
        return 'users';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Usuarios');
    }

    public function returnRoute(): string
    {
        return 'admin_users';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('username', new TranslatableMessage('Nombre de usuario')),
            new ExportColumnDefinition('roles', new TranslatableMessage('Roles')),
            new ExportColumnDefinition('account', new TranslatableMessage('Cuenta')),
            new ExportColumnDefinition('active', new TranslatableMessage('Activo')),
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha de registro')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminUsersExportType::class;
    }

    public function createFilter(): object
    {
        return new UsersExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof UsersExportFilter) {
            return [];
        }

        return [
            'username' => $filter->username,
            'role' => $filter->role?->value,
            'accountId' => $filter->account instanceof Account ? (string) $filter->account->id() : null,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new UsersExportFilter();

        $filter->username = isset($criteria['username']) ? (string) $criteria['username'] : null;
        $filter->role = isset($criteria['role'])
            ? Role::from((string) $criteria['role'])
            : null;

        if (isset($criteria['accountId'])) {
            $filter->account = $this->accountRepository->find(new AccountId((string) $criteria['accountId']));
        }

        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof UsersExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->userRepository->findByFilter($filter) as $user) {
            yield [
                'username' => $user->username(),
                'roles' => implode(', ', array_map(
                    fn(Role $role): string => $this->translator->trans($role->value, [], 'roles'),
                    $user->roles(),
                )),
                'account' => (string) ($user->account() ?? ''),
                'active' => $user->isActive()
                    ? $this->translator->trans('Sí')
                    : $this->translator->trans('No'),
                'registeredAt' => $user->registeredAt()?->format('d/m/Y') ?? '',
            ];
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new User(
            id: UserId::generate(),
            accountId: $this->currentAccountIdProvider?->currentAccountId() ?? $user->accountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }
}
