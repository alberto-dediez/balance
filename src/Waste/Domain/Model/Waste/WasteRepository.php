<?php

namespace App\Waste\Domain\Model\Waste;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;

interface WasteRepository
{
    public function add(Waste $account): void;

    public function remove(Waste $account): void;

    public function find(WasteId $accountId): ?Waste;

    public function findByName(string $name): ?Waste;

    public function findByFilter(WasteFilter $filter): array;

    public function totalResultsByFilter(WasteFilter $filter): int;
}