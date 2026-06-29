<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Import;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportOvernightStayResponse implements CommandResponse
{
    public function __construct(
        public OvernightStayId $overnightStayId,
    ) {}
}
