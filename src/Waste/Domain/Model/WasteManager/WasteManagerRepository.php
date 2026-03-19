<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\WasteManager;

interface WasteManagerRepository
{
    public function add(WasteManager $wasteManager): void;

    public function remove(WasteManager $wasteManager): void;

    public function find(WasteManagerId $wasteManagerId): ?WasteManager;

    public function findByFilter(WasteManagerFilter $filter): array;

    public function totalResultsByFilter(WasteManagerFilter $filter): int;
}
