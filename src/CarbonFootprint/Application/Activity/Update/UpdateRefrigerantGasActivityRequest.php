<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Shared\Application\CommandBus\Command;

class UpdateRefrigerantGasActivityRequest implements Command
{
    public function __construct(
        public ActivityId $activityId,
        public RefrigerantGasId $refrigerantGasId,
        public Quantity $quantity,
        public int $year,
        public EmissionFactorGroupId $emissionFactorGroupId,
        public ?string $source = null,
    ) {}
}
