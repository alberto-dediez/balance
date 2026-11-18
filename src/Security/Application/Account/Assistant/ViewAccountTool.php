<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Assistant;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\View\ViewAccountRequest;
use App\Security\Application\Account\View\ViewAccountService;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ViewAccountTool implements AssistantTool
{
    public function __construct(
        private readonly ViewAccountService $viewAccount,
        private readonly AccountAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'view_account';
    }

    public function label(): string
    {
        return 'Ver cuenta';
    }

    public function description(): string
    {
        return 'Devuelve el detalle de una cuenta: nombre, estado, si permite tokens de API y alta. Necesita el id de la cuenta (búscalo antes con list_accounts).';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('accountId', 'Cuenta', 'Id de la cuenta.')->required(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Account(AccountId::generate()), Action::View, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $account = ($this->viewAccount)(new ViewAccountRequest(new AccountId((string) $arguments->string('accountId'))), $user)->account;

        return new ToolResult(
            summary: sprintf('Cuenta «%s»', $account->name()),
            data: [
                'id' => (string) $account->id(),
                'name' => $account->name(),
                'active' => $account->isActive(),
                'apiTokensEnabled' => $account->allowsApiTokens(),
                'registeredAt' => $context->dateTime($account->registeredAt()),
            ],
            references: ['accountId' => (string) $account->id()],
            link: new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']),
        );
    }
}
