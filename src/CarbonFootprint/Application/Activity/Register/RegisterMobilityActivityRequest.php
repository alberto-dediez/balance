<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Application\CommandBus\Command;

class RegisterMobilityActivityRequest implements Command
{
    public function __construct(
        public MobilityId $mobilityId,
        public Quantity $quantity,
        public int $year,
    ) {}
}
