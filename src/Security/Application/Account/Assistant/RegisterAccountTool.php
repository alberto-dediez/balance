<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Assistant;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolPreview;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Application\Assistant\WriteAssistantTool;

class RegisterAccountTool implements WriteAssistantTool
{
    public function __construct(
        private readonly RegisterAccountService $registerAccount,
        private readonly AccountAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'register_account';
    }

    public function label(): string
    {
        return 'Registrar cuenta';
    }

    public function description(): string
    {
        return 'Registra una cuenta nueva. Comprueba antes con list_accounts que no existe ya. Indica siempre si queda activa.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Write;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('name', 'Nombre', 'Nombre de la cuenta.')->required(),
            ToolParameter::boolean('active', 'Activa', 'true si la cuenta queda activa; false si queda inactiva y sus usuarios no pueden entrar.')->required(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Account(AccountId::generate()), Action::Register, $user);
    }

    public function preview(ToolArguments $arguments, User $user, ToolContext $context): ToolPreview
    {
        if (!$this->isAvailableTo($user)) {
            throw InvalidToolArgumentsException::because('No tienes permiso para registrar cuentas.');
        }

        return new ToolPreview();
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $response = ($this->registerAccount)(
            new RegisterAccountRequest(
                name: (string) $arguments->string('name'),
                active: (bool) $arguments->bool('active'),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('Cuenta «%s» registrada', $response->name),
            data: ['accountId' => (string) $response->accountId, 'name' => $response->name, 'active' => $response->active],
            references: ['accountId' => (string) $response->accountId],
            link: new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']),
        );
    }
}
