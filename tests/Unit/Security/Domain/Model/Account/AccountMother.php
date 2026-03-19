<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;

class AccountMother
{
    public static function registeredAccount(): Account
    {
        return self::fromName('name');
    }

    public static function fromName(string $name): Account
    {
        $accountId = AccountId::generate();

        $account = new Account($accountId);
        $account->register($name, true);

        return $account;
    }

    public static function fromNameAndId(string $name, string $id): Account
    {
        $account = new Account(
            id: new AccountId($id),
        );
        $account->register($name, true);

        return $account;
    }

    public static function random(): Account
    {
        return self::fromName('account-' . random_int(100, 999));
    }
}
