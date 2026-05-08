<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Property\Domain\Model\Property\PropertyId;

final readonly class PreventionPlanVersionPropertyLink
{
    public function __construct(
        private PreventionPlanVersion $preventionPlanVersion,
        private PropertyId $propertyId,
    ) {}

    public function preventionPlanVersion(): PreventionPlanVersion
    {
        return $this->preventionPlanVersion;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }
}
