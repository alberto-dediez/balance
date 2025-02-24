<?php

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\Role;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;

class AccountMother
{
    public static function registeredAccount()
    {   
        return self::fromName('name');
    }

    public static function fromName($name)
    {
        $accountId = AccountId::generate();

        $account = new Account($accountId);
        $account->register($name, true);

        return $account;
    }
}