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

    public static function fromNameAndId(string $name, string $id)
    {
        $account = new Account(
            id: new AccountId($id)
        );
        $account->register($name, true);

        return $account;
    }

    public static function random()
    {
        return self::fromName('account-' . rand(100, 999));
    }
}