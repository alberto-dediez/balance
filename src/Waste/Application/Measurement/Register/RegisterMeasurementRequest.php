<?php

namespace App\Waste\Application\Measurement\Register;

use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\ContainerId;

class RegisterMeasurementRequest
{
    public function __construct(
        public ContainerId $containerId,
        public int $quantity,
        public float $load,
        public UserId $registeredBy,
        public ?string $justification = null
    ) {
        
    }
}