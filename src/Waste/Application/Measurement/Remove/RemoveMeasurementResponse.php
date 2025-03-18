<?php

namespace App\Waste\Application\Measurement\Remove;

use App\Waste\Domain\Model\Measurement\MeasurementId;

class RemoveMeasurementResponse
{
    public function __construct(public MeasurementId $measurementId)
    {
        
    }
}