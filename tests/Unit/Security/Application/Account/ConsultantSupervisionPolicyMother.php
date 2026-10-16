<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\ConsultantSupervisionPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;

class ConsultantSupervisionPolicyMother
{
    /**
     * @param list<Account> $accounts
     * @param list<User>    $users
     */
    public static function create(array $accounts = [], array $users = []): ConsultantSupervisionPolicy
    {
        $indexedAccounts = [];

        foreach ($accounts as $account) {
            $indexedAccounts[(string) $account->id()] = $account;
        }

        $indexedUsers = [];

        foreach ($users as $user) {
            $indexedUsers[(string) $user->id()] = $user;
        }

        return new ConsultantSupervisionPolicy(
            new InMemoryAccountRepository($indexedAccounts),
            new InMemoryUserRepository($indexedUsers),
        );
    }
}
