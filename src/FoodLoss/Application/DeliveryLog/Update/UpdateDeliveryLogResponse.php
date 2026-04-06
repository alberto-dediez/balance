<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Update;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateDeliveryLogResponse implements CommandResponse
{
    public function __construct(
        public DeliveryLogId $deliveryLogId,
        public string $productType,
    ) {}
}
