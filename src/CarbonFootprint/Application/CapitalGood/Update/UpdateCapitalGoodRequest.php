<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Update;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateCapitalGoodRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public CapitalGoodId $capitalGoodId,
        public string $name,
        public CapitalGoodCategory $category,
        public Unit $unit,
        public array $emissionFactors = [],
    ) {}
}
