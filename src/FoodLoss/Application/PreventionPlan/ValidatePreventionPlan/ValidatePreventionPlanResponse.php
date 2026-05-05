<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidatePreventionPlan;

use Symfony\Component\Translation\TranslatableMessage;

class ValidatePreventionPlanResponse
{
    public bool $hasErrors;

    /**
     * @param array<array{propertyName: string, errors: array<mixed>}> $invalidProperties
     * @param TranslatableMessage[]                                     $planErrors
     */
    public function __construct(
        public bool $hasInvalidProperties,
        public array $invalidProperties = [],
        public array $planErrors = [],
    ) {
        $this->hasErrors = $hasInvalidProperties || [] !== $planErrors;
    }
}
