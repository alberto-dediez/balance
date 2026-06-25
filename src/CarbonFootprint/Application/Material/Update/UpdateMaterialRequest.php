<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Update;

use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateMaterialRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public MaterialId $materialId,
        public string $name,
        public MaterialCategory $category,
        public Unit $unit,
        public ?string $unitWeight = null,
        public array $emissionFactors = [],
    ) {}
}
