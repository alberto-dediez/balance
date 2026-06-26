<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

interface CateringItemRepository
{
    public function add(CateringItem $cateringItem): void;

    public function remove(CateringItem $cateringItem): void;

    public function find(CateringItemId $cateringItemId): ?CateringItem;

    /** @return array<CateringItem> */
    public function findByFilter(CateringItemFilter $filter): array;

    public function totalResultsByFilter(CateringItemFilter $filter): int;
}
