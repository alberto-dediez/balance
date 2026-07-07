<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Register;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterCateringItemRequest implements Command
{
    public function __construct(
        public string $name = '',
        public CateringItemCategory $category = CateringItemCategory::Others,
        public Unit $unit = Unit::Kilogram,
    ) {}
}
