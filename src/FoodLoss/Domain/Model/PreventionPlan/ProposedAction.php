<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Property\Domain\Model\Property\PropertyId;

final readonly class ProposedAction
{
    public function __construct(
        private PreventionPlanVersion $preventionPlanVersion,
        private PropertyId $propertyId,
        private ActionId $actionId,
    ) {}

    public function preventionPlanVersion(): PreventionPlanVersion
    {
        return $this->preventionPlanVersion;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
    }
}
