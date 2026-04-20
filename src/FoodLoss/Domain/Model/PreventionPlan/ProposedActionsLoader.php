<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Property\Domain\Model\Property\PropertyId;

interface ProposedActionsLoader
{
    /** @return ProposedActionSuggestion[] */
    public function suggestFor(PropertyId $propertyId): array;
}
