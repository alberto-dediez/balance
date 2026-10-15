<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

interface PurchaseServiceRepository
{
    public function add(PurchaseService $purchaseService): void;

    public function remove(PurchaseService $purchaseService): void;

    public function find(PurchaseServiceId $purchaseServiceId): ?PurchaseService;

    /** @return array<PurchaseService> */
    public function findByFilter(PurchaseServiceFilter $filter): array;

    public function totalResultsByFilter(PurchaseServiceFilter $filter): int;
}
