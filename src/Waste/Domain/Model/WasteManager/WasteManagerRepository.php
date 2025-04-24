<?php

namespace App\Waste\Domain\Model\WasteManager;

use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerFilter;

interface WasteManagerRepository
{
    public function add(WasteManager $wasteManager): void;

    public function remove(WasteManager $wasteManager): void;

    public function find(WasteManagerId $wasteManagerId): ?WasteManager;

    public function findByFilter(WasteManagerFilter $filter): array;

    public function totalResultsByFilter(WasteManagerFilter $filter): int;
}