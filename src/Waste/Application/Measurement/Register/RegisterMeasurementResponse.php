<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement\Register;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;

class RegisterMeasurementResponse implements CommandResponse
{
    public function __construct(
        public MeasurementId $measurementId,
        public ContainerId $containerId,
        public int $quantity,
        public float $load,
        public UserId $registeredBy,
        public ?string $justification,
        public ?string $reference,
    ) {}
}
