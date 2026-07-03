<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Waste;

interface WasteRepository
{
    public function add(Waste $waste): void;

    public function remove(Waste $waste): void;

    public function find(WasteId $wasteId): ?Waste;

    /** @return array<Waste> */
    public function findByFilter(WasteFilter $filter): array;

    public function totalResultsByFilter(WasteFilter $filter): int;
}
