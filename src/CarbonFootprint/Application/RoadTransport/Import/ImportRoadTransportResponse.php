<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Import;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportRoadTransportResponse implements CommandResponse
{
    public function __construct(
        public RoadTransportId $roadTransportId,
    ) {}
}
