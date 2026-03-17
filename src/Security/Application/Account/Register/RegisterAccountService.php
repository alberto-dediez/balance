<?php

namespace App\Security\Application\Account\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\Account\Account;
use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RegisterAccountService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private AccountRepository $accountRepository, private AccountAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterAccountRequest $request, User $user): RegisterAccountResponse
    {
        $id = AccountId::generate();

        $account = new Account($id);

        $this->authorizer->denyUnlessCanDoThat($account, Action::Register, $user);

        $account->register($request->name, $request->active);

        $this->accountRepository->add($account);

        return new RegisterAccountResponse($account->id(), $account->name(), $account->isActive());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterAccountRequest;
    }
}
