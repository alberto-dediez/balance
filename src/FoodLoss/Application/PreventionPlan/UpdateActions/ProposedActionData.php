<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\UpdateActions;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Property\Domain\Model\Property\PropertyId;

class ProposedActionData
{
    public function __construct(
        public ?PropertyId $propertyId = null,
        public ?ActionId $actionId = null,
    ) {}
}
