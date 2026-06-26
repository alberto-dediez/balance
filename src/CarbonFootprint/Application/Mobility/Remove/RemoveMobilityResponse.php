<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Remove;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveMobilityResponse implements CommandResponse
{
    public function __construct(public MobilityId $mobilityId) {}
}
