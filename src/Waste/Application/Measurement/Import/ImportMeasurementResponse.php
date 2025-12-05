<?php

namespace App\Waste\Application\Measurement\Import;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Measurement\MeasurementId;

class ImportMeasurementResponse implements CommandResponse
{
    public function __construct(
        public MeasurementId $measurementId
    ) {

    }
}
