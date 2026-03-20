<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Waste;

interface WasteRepository
{
    public function add(Waste $waste): void;

    public function remove(Waste $waste): void;

    public function find(WasteId $wasteId): ?Waste;

    public function findByFilter(WasteFilter $filter): array;

    public function totalResultsByFilter(WasteFilter $filter): int;
}
