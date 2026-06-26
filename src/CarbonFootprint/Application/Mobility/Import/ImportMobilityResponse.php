<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Import;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportMobilityResponse implements CommandResponse
{
    public function __construct(
        public MobilityId $mobilityId,
    ) {}
}
