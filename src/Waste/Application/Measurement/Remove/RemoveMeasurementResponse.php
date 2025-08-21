<?php

namespace App\Waste\Application\Measurement\Remove;

use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveMeasurementResponse implements CommandResponse
{
    public function __construct(public MeasurementId $measurementId)
    {
        
    }
}