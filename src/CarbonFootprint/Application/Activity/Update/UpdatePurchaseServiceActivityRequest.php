<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Application\CommandBus\Command;

class UpdatePurchaseServiceActivityRequest implements Command
{
    public function __construct(
        public ActivityId $activityId,
        public PurchaseServiceId $purchaseServiceId,
        public Quantity $quantity,
        public int $year,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
