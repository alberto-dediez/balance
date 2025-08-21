<?php

namespace App\Waste\Application\Measurement\Update;

use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMeasurementResponse implements CommandResponse
{
    public function __construct(
        public MeasurementId $measurementId, 
        public int $quantity,
        public float $load,
        public ?string $justification
        
    ) {
        
    }
}