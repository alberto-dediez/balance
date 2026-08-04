<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\AccountProject;

interface AccountProjectRepository
{
    public function add(AccountProject $accountProject): void;

    public function remove(AccountProject $accountProject): void;

    public function find(AccountProjectId $accountProjectId): ?AccountProject;

    /** @return array<AccountProject> */
    public function findByFilter(AccountProjectFilter $filter): array;

    public function totalResultsByFilter(AccountProjectFilter $filter): int;
}
