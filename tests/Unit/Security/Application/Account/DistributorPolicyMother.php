<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;

class DistributorPolicyMother
{
    /**
     * @param list<Account> $accounts
     */
    public static function create(array $accounts = []): DistributorPolicy
    {
        $indexedAccounts = [];

        foreach ($accounts as $account) {
            $indexedAccounts[(string) $account->id()] = $account;
        }

        return new DistributorPolicy(new InMemoryAccountRepository($indexedAccounts));
    }
}
