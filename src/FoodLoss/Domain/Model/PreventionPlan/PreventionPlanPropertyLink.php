<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Property\Domain\Model\Property\PropertyId;

final readonly class PreventionPlanPropertyLink
{
    public function __construct(
        private PreventionPlan $preventionPlan,
        private PropertyId $propertyId,
    ) {}

    public function preventionPlan(): PreventionPlan
    {
        return $this->preventionPlan;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }
}
