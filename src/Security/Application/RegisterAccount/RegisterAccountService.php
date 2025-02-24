<?php

namespace App\Security\Application\RegisterAccount;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

class RegisterAccountService
{
    public function __construct(private AccountRepository $accountRepository)
    {
    }

    public function __invoke(RegisterAccountRequest $request): RegisterAccountResponse
    {
        $id = AccountId::generate();

        $account = new Account($id);
        $account->register($request->name, $request->active);

        $this->accountRepository->add($account);

        return new RegisterAccountResponse($account->id(), $account->name(), $account->isActive());
    }
}