<?php

namespace App\Waste\Application\Measurement\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Measurement\MeasurementId;

class RemoveMeasurementRequest implements Command
{
    public function __construct(public MeasurementId $measurementId)
    {

    }
}