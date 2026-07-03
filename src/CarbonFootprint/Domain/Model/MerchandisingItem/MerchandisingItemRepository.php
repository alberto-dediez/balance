<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

interface MerchandisingItemRepository
{
    public function add(MerchandisingItem $merchandisingItem): void;

    public function remove(MerchandisingItem $merchandisingItem): void;

    public function find(MerchandisingItemId $merchandisingItemId): ?MerchandisingItem;

    /** @return array<MerchandisingItem> */
    public function findByFilter(MerchandisingItemFilter $filter): array;

    public function totalResultsByFilter(MerchandisingItemFilter $filter): int;
}
