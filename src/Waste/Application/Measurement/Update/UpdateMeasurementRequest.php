<?php

namespace App\Waste\Application\Measurement\Update;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Measurement\MeasurementId;

class UpdateMeasurementRequest implements Command
{
    public function __construct(
        public MeasurementId $measurementId, 
        public int $quantity,
        public float $load,
        public ?string $justification
    ) {

    }
}