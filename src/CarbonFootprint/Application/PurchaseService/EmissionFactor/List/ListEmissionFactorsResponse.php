<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;

class ListEmissionFactorsResponse
{
    /** @param array<PurchaseService> $purchaseServices */
    public function __construct(
        public array $purchaseServices,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
