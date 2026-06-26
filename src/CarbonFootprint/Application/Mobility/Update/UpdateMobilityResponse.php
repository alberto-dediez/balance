<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Update;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMobilityResponse implements CommandResponse
{
    public function __construct(
        public MobilityId $mobilityId,
        public string $name,
    ) {}
}
