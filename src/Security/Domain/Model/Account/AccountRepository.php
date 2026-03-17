<?php

namespace App\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;

interface AccountRepository
{
    public function add(Account $account): void;

    public function remove(Account $account): void;

    public function find(AccountId $accountId): ?Account;

    public function findByName(string $name): ?Account;

    /** @return list<Account> */
    public function findByFilter(AccountFilter $filter): array;

    public function totalResultsByFilter(AccountFilter $filter): int;
}
