<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

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
