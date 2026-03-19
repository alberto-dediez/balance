<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Waste;

interface WasteRepository
{
    public function add(Waste $account): void;

    public function remove(Waste $account): void;

    public function find(WasteId $accountId): ?Waste;

    public function findByFilter(WasteFilter $filter): array;

    public function totalResultsByFilter(WasteFilter $filter): int;
}
