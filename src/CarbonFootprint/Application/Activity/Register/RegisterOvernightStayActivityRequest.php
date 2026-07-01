<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Application\CommandBus\Command;

class RegisterOvernightStayActivityRequest implements Command
{
    public function __construct(
        public OvernightStayId $overnightStayId,
        public Quantity $quantity,
        public int $year,
    ) {}
}
