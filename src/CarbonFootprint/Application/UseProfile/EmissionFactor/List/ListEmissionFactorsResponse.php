<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;

class ListEmissionFactorsResponse
{
    /** @param array<UseProfile> $useProfiles */
    public function __construct(
        public array $useProfiles,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
