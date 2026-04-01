<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Update;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class UpdateCriticalPointRequest implements Command
{
    public function __construct(
        public CriticalPointId $criticalPointId,
        public string $name = '',
        public ?PropertyId $propertyId = null,
        public SupplyChainStage $supplyChainStage = SupplyChainStage::Receiving,
    ) {}
}
