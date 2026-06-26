<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Register;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterRoadTransportResponse implements CommandResponse
{
    public function __construct(
        public RoadTransportId $roadTransportId,
        public string $name,
    ) {}
}
