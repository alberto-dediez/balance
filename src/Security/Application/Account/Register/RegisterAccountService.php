<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Register;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterAccountService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private AccountRepository $accountRepository, private AccountAuthorizer $authorizer) {}

    public function __invoke(RegisterAccountRequest $request, User $user): RegisterAccountResponse
    {
        $id = AccountId::generate();

        $account = new Account($id);

        $this->authorizer->denyUnlessCanDoThat($account, Action::Register, $user);

        $account->register($request->name, $request->active, $request->emissionFactorGroupId, $request->consultant);

        $this->accountRepository->add($account);

        return new RegisterAccountResponse($account->id(), $account->name(), $account->isActive(), $account->isConsultant());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterAccountRequest;
    }
}
