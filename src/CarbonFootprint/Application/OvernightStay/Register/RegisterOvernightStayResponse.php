<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Register;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterOvernightStayResponse implements CommandResponse
{
    public function __construct(
        public OvernightStayId $overnightStayId,
        public string $name,
    ) {}
}
