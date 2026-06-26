<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Update;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateCateringItemRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public CateringItemId $cateringItemId,
        public string $name,
        public CateringItemCategory $category,
        public Unit $unit,
        public ?string $unitWeight = null,
        public array $emissionFactors = [],
    ) {}
}
