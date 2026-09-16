<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\Register;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCompanyCarbonFootprintResponse implements CommandResponse
{
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
    ) {}
}
