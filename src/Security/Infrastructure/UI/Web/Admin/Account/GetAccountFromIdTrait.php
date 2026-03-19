<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;

trait GetAccountFromIdTrait
{
    private function getAccountFromId(string $accountId, AccountRepository $accountRepository): Account
    {
        $account = $accountRepository->find(new AccountId($accountId));

        if (null == $account) {
            throw $this->createNotFoundException('Account not found');
        }

        return $account;
    }
}
