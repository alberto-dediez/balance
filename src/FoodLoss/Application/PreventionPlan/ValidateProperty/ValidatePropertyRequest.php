<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidateProperty;

use App\Property\Domain\Model\Property\PropertyId;

class ValidatePropertyRequest
{
    public function __construct(public PropertyId $propertyId) {}
}
