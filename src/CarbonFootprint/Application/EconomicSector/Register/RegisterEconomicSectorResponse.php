<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Register;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEconomicSectorResponse implements CommandResponse
{
    public function __construct(
        public EconomicSectorId $economicSectorId,
        public string $name,
    ) {}
}
