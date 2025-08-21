<?php

namespace App\Waste\Application\Measurement\Register;

use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterMeasurementResponse implements CommandResponse
{
    public function __construct(
        public MeasurementId $measurementId, 
        public ContainerId $containerId, 
        public int $quantity,
        public float $load,
        public UserId $registeredBy,
        public ?string $justification
    ) {
        
    }
}