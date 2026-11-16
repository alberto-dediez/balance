<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Assistant;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\Update\UpdateAccountRequest;
use App\Security\Application\Account\Update\UpdateAccountService;
use App\Security\Application\Account\View\ViewAccountRequest;
use App\Security\Application\Account\View\ViewAccountService;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
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

class UpdateAccountTool implements WriteAssistantTool
{
    private const array FIELDS = ['name', 'active'];

    public function __construct(
        private readonly ViewAccountService $viewAccount,
        private readonly UpdateAccountService $updateAccount,
        private readonly AccountRepository $accounts,
        private readonly AccountAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'update_account';
    }

    public function label(): string
    {
        return 'Modificar cuenta';
    }

    public function description(): string
    {
        return 'Cambia el nombre o el estado de una cuenta; solo cambia lo recibido. Con active=false la cuenta queda inactiva y sus usuarios dejan de poder entrar.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Write;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('accountId', 'Cuenta', 'Id de la cuenta.')->required(),
            ToolParameter::string('name', 'Nombre', 'Nuevo nombre de la cuenta.'),
            ToolParameter::boolean('active', 'Activa', 'Nuevo estado de la cuenta.'),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Account(AccountId::generate()), Action::Edit, $user);
    }

    public function preview(ToolArguments $arguments, User $user, ToolContext $context): ToolPreview
    {
        $this->assertSomeField($arguments);

        $displays = [];

        if ($arguments->has('accountId')) {
            $account = $this->accounts->find(new AccountId((string) $arguments->string('accountId')));

            if (!$account instanceof Account) {
                throw InvalidToolArgumentsException::because('La cuenta no existe.');
            }

            if (!$this->authorizer->canDoThat($account, Action::Edit, $user)) {
                throw InvalidToolArgumentsException::because('No tienes permiso para editar esta cuenta.');
            }

            $displays['accountId'] = $account->name();
        }

        if (false === $arguments->bool('active')) {
            $displays['active'] = 'false — desactiva la cuenta y sus usuarios dejan de poder entrar';
        }

        return new ToolPreview($displays);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $this->assertSomeField($arguments);

        $account = ($this->viewAccount)(new ViewAccountRequest(new AccountId((string) $arguments->string('accountId'))), $user)->account;

        $response = ($this->updateAccount)(
            new UpdateAccountRequest(
                accountId: $account->id(),
                name: $arguments->string('name') ?? $account->name(),
                active: $arguments->bool('active') ?? $account->isActive(),
                apiTokensEnabled: $account->allowsApiTokens(),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('Cuenta «%s» modificada', $response->name),
            data: ['accountId' => (string) $response->accountId, 'name' => $response->name, 'active' => $response->active],
            references: ['accountId' => (string) $response->accountId],
            link: new ToolLink('Ver cuenta', 'admin_accounts_edit', ['accountId']),
        );
    }

    private function assertSomeField(ToolArguments $arguments): void
    {
        foreach (self::FIELDS as $field) {
            if ($arguments->has($field)) {
                return;
            }
        }

        throw InvalidToolArgumentsException::because('Indica al menos un campo que modificar además de la cuenta.');
    }
}
