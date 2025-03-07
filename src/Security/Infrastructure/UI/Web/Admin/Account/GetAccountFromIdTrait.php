<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Domain\Model\Account\AccountRepository;

trait GetAccountFromIdTrait
{
    private function getAccountFromId(string $accountId, AccountRepository $accountRepository): Account
    {
        $account = $accountRepository->find(new AccountId($accountId));

        if (null == $account) {
            throw $this->createNotFoundException(new TranslatableMessage("La cuenta no existe"));
        }

        return $account;
    }
}