<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\Surplus\SurplusId;

interface DeliveryLogRepository
{
    public function add(DeliveryLog $deliveryLog): void;

    public function remove(DeliveryLog $deliveryLog): void;

    public function find(DeliveryLogId $deliveryLogId): ?DeliveryLog;

    public function findBySurplusId(SurplusId $surplusId): ?DeliveryLog;

    /** @return array<DeliveryLog> */
    public function findByFilter(DeliveryLogFilter $filter): array;

    public function totalResultsByFilter(DeliveryLogFilter $filter): int;
}
