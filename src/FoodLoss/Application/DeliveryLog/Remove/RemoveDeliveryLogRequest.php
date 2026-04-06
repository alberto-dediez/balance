<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Remove;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\Shared\Application\CommandBus\Command;

class RemoveDeliveryLogRequest implements Command
{
    public function __construct(public DeliveryLogId $deliveryLogId) {}
}
