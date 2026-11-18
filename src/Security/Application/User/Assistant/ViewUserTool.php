<?php

declare(strict_types=1);

namespace App\Security\Application\User\Assistant;

use App\Security\Application\Action;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Application\User\View\ViewUserRequest;
use App\Security\Application\User\View\ViewUserService;
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

class ViewUserTool implements AssistantTool
{
    public function __construct(
        private readonly ViewUserService $viewUser,
        private readonly UserAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'view_user';
    }

    public function label(): string
    {
        return 'Ver usuario';
    }

    public function description(): string
    {
        return 'Devuelve el detalle de un usuario: roles, cuenta, estado, alta, doble factor e idioma preferido. Necesita el id del usuario (búscalo antes con list_users).';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('userId', 'Usuario', 'Id del usuario.')->required(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new User(UserId::generate()), Action::View, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $found = ($this->viewUser)(new ViewUserRequest(new UserId((string) $arguments->string('userId'))), $user)->user;
        $accountId = $found->accountId();

        return new ToolResult(
            summary: sprintf('Usuario «%s»', $found->username()),
            data: [
                'id' => (string) $found->id(),
                'href' => ToolLink::href('admin_users_edit', ['userId' => (string) $found->id()]),
                'username' => $found->username(),
                'roles' => array_map(static fn(Role $role): string => $role->value, $found->roles()),
                'account' => $accountId instanceof AccountId ? ['id' => (string) $accountId, 'href' => ToolLink::href('admin_accounts_edit', ['accountId' => (string) $accountId]), 'name' => $found->account()?->name()] : null,
                'active' => $found->isActive(),
                'registeredAt' => $context->dateTime($found->registeredAt()),
                'twoFactor' => $found->hasTwoFactor(),
                'preferredLocale' => $found->preferredLocale()?->value,
            ],
            references: ['userId' => (string) $found->id()],
            link: new ToolLink('Ver usuario', 'admin_users_edit', ['userId']),
        );
    }
}
