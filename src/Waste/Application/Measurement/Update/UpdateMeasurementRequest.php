<?php

namespace App\Waste\Application\Measurement\Update;

use App\Waste\Domain\Model\Measurement\MeasurementId;

class UpdateMeasurementRequest
{
    public function __construct(
        public MeasurementId $measurementId, 
        public int $quantity,
        public float $load,
        public ?string $justification
    ) {

    }
}