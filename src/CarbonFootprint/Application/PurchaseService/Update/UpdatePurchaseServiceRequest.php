<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Update;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdatePurchaseServiceRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public PurchaseServiceId $purchaseServiceId,
        public string $name,
        public PurchaseServiceCategory $category,
        public Unit $unit,
        public array $emissionFactors = [],
    ) {}
}
