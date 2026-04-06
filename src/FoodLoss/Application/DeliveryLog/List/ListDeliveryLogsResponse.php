<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\List;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;

class ListDeliveryLogsResponse
{
    /** @param array<DeliveryLog> $deliveryLogs */
    public function __construct(public array $deliveryLogs = [], public int $totalResults = 0) {}
}
