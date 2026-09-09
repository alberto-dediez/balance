<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;

class AccountMother
{
    public static function registeredAccount(): Account
    {
        return self::fromName('name');
    }

    public static function fromName(string $name, ?EmissionFactorGroupId $emissionFactorGroupId = null): Account
    {
        $accountId = AccountId::generate();

        $account = new Account($accountId);
        $account->register($name, true, $emissionFactorGroupId ?? EmissionFactorGroupMother::default()->id());

        return $account;
    }

    public static function fromNameAndId(string $name, string $id, ?EmissionFactorGroupId $emissionFactorGroupId = null): Account
    {
        $account = new Account(
            id: new AccountId($id),
        );
        $account->register($name, true, $emissionFactorGroupId ?? EmissionFactorGroupMother::default()->id());

        return $account;
    }

    public static function consultantAccount(): Account
    {
        $account = new Account(AccountId::generate());
        $account->register('consultant-' . random_int(100, 999), true, EmissionFactorGroupMother::default()->id(), true);

        return $account;
    }

    public static function fromNameWithConsultant(string $name, AccountId $consultantAccountId): Account
    {
        $account = self::fromName($name);
        $account->changeConsultant($consultantAccountId);

        return $account;
    }

    public static function random(): Account
    {
        return self::fromName('account-' . random_int(100, 999));
    }
}
