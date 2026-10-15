<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Register;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterPurchaseServiceRequest implements Command
{
    public function __construct(
        public string $name = '',
        public PurchaseServiceCategory $category = PurchaseServiceCategory::Others,
        public Unit $unit = Unit::Euro,
    ) {}
}
