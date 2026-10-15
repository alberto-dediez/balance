<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

class ListEmissionFactorsResponse
{
    /** @param array<CapitalGood> $capitalGoods */
    public function __construct(
        public array $capitalGoods,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
