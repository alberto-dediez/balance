<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

class ListEmissionFactorsRequest
{
    public function __construct(
        public EmissionFactorGroupId $emissionFactorGroupId,
        public ?int $year = null,
    ) {}
}
