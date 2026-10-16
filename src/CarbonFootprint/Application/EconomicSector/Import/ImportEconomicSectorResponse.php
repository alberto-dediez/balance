<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Import;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportEconomicSectorResponse implements CommandResponse
{
    public function __construct(
        public EconomicSectorId $economicSectorId,
    ) {}
}
