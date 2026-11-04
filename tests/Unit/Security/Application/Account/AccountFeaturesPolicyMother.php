<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;

class AccountFeaturesPolicyMother
{
    /**
     * @param list<Account> $accounts
     */
    public static function create(array $accounts = []): AccountFeaturesPolicy
    {
        $indexedAccounts = [];

        foreach ($accounts as $account) {
            $indexedAccounts[(string) $account->id()] = $account;
        }

        return new AccountFeaturesPolicy(new InMemoryAccountRepository($indexedAccounts));
    }

    public static function forAccount(AccountId $accountId, AccountFeatures $features): AccountFeaturesPolicy
    {
        return self::create([AccountMother::withFeatures('account', (string) $accountId, $features)]);
    }
}
