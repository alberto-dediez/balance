<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Register;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterDeliveryLogResponse implements CommandResponse
{
    public function __construct(
        public DeliveryLogId $deliveryLogId,
        public FoodType $productType,
    ) {}
}
