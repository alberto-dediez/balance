<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Update;

use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Shared\Application\CommandBus\Command;

class UpdateOvernightStayRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public OvernightStayId $overnightStayId,
        public string $name,
        public CategoryOvernight $category,
        public array $emissionFactors = [],
    ) {}
}
