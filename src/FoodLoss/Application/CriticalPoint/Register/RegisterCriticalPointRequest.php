<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Register;

use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class RegisterCriticalPointRequest implements Command
{
    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     */
    public function __construct(
        public string $name = '',
        public ?PropertyId $propertyId = null,
        public SupplyChainStage $supplyChainStage = SupplyChainStage::Receiving,
        public array $serviceCategoryIds = [],
    ) {}
}
