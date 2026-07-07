<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Register;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterMerchandisingItemRequest implements Command
{
    public function __construct(
        public string $name = '',
        public MerchandisingItemCategory $category = MerchandisingItemCategory::Others,
        public Unit $unit = Unit::Kilogram,
    ) {}
}
