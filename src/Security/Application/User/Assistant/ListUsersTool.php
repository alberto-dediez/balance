<?php

declare(strict_types=1);

namespace App\Security\Application\User\Assistant;

use App\Security\Application\Account\View\ViewAccountRequest;
use App\Security\Application\Account\View\ViewAccountService;
use App\Security\Application\Action;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListUsersTool implements AssistantTool
{
    public function __construct(
        private readonly ListUsersService $listUsers,
        private readonly ViewAccountService $viewAccount,
        private readonly UserAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'list_users';
    }

    public function label(): string
    {
        return 'Listar usuarios';
    }

    public function description(): string
    {
        return 'Lista los usuarios de la plataforma por nombre de usuario, rol y cuenta. Después se ve el detalle de uno con view_user.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('username', 'Usuario', 'Parte del nombre de usuario (normalmente el email).'),
            ToolParameter::enum('role', 'Rol', 'Rol del usuario.', array_column(Role::cases(), 'value')),
            ToolParameter::id('accountId', 'Cuenta', 'Id de la cuenta a la que pertenecen los usuarios.'),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new User(UserId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $role = $arguments->string('role');
        $accountId = $arguments->string('accountId');

        $response = ($this->listUsers)(
            new ListUsersRequest(
                username: $arguments->string('username'),
                role: null === $role ? null : Role::from($role),
                account: null === $accountId ? null : ($this->viewAccount)(new ViewAccountRequest(new AccountId($accountId)), $user)->account,
                limit: min($arguments->limit(), ToolResult::ITEMS_MAX_COUNT),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d usuarios encontrados', $response->totalResults),
            data: [
                'users' => array_map(
                    fn(User $found): array => $this->userData($found, $context),
                    array_values($response->users),
                ),
                'total' => $response->totalResults,
            ],
            link: new ToolLink('Ver usuarios', 'admin_users'),
        );
    }

    /** @return array<string, mixed> */
    private function userData(User $user, ToolContext $context): array
    {
        $accountId = $user->accountId();

        return [
            'id' => (string) $user->id(),
            'username' => $user->username(),
            'roles' => array_map(static fn(Role $role): string => $role->value, $user->roles()),
            'account' => $accountId instanceof AccountId ? ['id' => (string) $accountId, 'name' => $user->account()?->name()] : null,
            'active' => $user->isActive(),
            'registeredAt' => $context->dateTime($user->registeredAt()),
            'twoFactor' => $user->hasTwoFactor(),
        ];
    }
}
