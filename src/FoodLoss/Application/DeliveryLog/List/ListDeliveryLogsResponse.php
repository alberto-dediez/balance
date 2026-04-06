<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\List;

class ListDeliveryLogsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog> $deliveryLogs */
    public function __construct(public array $deliveryLogs = [], public int $totalResults = 0) {}
}
