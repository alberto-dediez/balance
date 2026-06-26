<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Remove;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Shared\Application\CommandBus\Command;

class RemoveRoadTransportRequest implements Command
{
    public function __construct(public RoadTransportId $roadTransportId) {}
}
