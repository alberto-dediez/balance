<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Assistant;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Application\Account\List\ListAccountsService;
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

class ListAccountsTool implements AssistantTool
{
    public function __construct(
        private readonly ListAccountsService $listAccounts,
        private readonly AccountAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'list_accounts';
    }

    public function label(): string
    {
        return 'Listar cuentas';
    }

    public function description(): string
    {
        return 'Lista las cuentas de la plataforma por nombre y estado. Después se ve el detalle de una con view_account.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('name', 'Nombre', 'Parte del nombre de la cuenta.'),
            ToolParameter::boolean('active', 'Activa', 'true para las cuentas activas, false para las inactivas.'),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Account(AccountId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $response = ($this->listAccounts)(
            new ListAccountsRequest(
                name: $arguments->string('name'),
                active: $arguments->bool('active'),
                limit: min($arguments->limit(), ToolResult::ITEMS_MAX_COUNT),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d cuentas encontradas', $response->totalResults),
            data: [
                'accounts' => array_map(
                    static fn(Account $account): array => [
                        'id' => (string) $account->id(),
                        'href' => ToolLink::href('admin_accounts_edit', ['accountId' => (string) $account->id()]),
                        'name' => $account->name(),
                        'active' => $account->isActive(),
                        'registeredAt' => $context->dateTime($account->registeredAt()),
                    ],
                    array_values($response->accounts),
                ),
                'total' => $response->totalResults,
            ],
            link: new ToolLink('Ver cuentas', 'admin_accounts'),
        );
    }
}
