<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Update;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateRoadTransportResponse implements CommandResponse
{
    public function __construct(
        public RoadTransportId $roadTransportId,
        public string $name,
    ) {}
}
