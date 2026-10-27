<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;

class ReportPermissionsPolicyMother
{
    /**
     * @param list<Account> $accounts
     */
    public static function create(array $accounts = []): ReportPermissionsPolicy
    {
        $indexedAccounts = [];

        foreach ($accounts as $account) {
            $indexedAccounts[(string) $account->id()] = $account;
        }

        $repository = new InMemoryAccountRepository($indexedAccounts);

        return new ReportPermissionsPolicy($repository, new ImpersonationPolicy($repository));
    }
}
