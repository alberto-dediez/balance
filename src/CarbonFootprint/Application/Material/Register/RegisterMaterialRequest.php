<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Register;

use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterMaterialRequest implements Command
{
    public function __construct(
        public string $name = '',
        public MaterialCategory $category = MaterialCategory::Others,
        public Unit $unit = Unit::Kilogram,
        public ?string $unitWeight = null,
    ) {}
}
