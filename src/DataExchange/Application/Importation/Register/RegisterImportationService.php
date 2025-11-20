<?php

namespace App\DataExchange\Application\Importation\Register;

use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountResponse;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterImportationService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    /*public function __construct(private ImportationRepository $importationRepository, private AccountAuthorizer $authorizer)
    {
    }*/

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
