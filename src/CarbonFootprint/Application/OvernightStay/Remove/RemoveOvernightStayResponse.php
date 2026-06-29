<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Remove;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveOvernightStayResponse implements CommandResponse
{
    public function __construct(public OvernightStayId $overnightStayId) {}
}
