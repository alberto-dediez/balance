<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Update;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateMerchandisingItemRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public MerchandisingItemId $merchandisingItemId,
        public string $name,
        public MerchandisingItemCategory $category,
        public Unit $unit,
        public array $emissionFactors = [],
    ) {}
}
