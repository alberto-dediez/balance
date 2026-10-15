<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Register;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterCapitalGoodRequest implements Command
{
    public function __construct(
        public string $name = '',
        public CapitalGoodCategory $category = CapitalGoodCategory::Others,
        public Unit $unit = Unit::Euro,
    ) {}
}
