<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Update;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateOvernightStayResponse implements CommandResponse
{
    public function __construct(
        public OvernightStayId $overnightStayId,
        public string $name,
    ) {}
}
