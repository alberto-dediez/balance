<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidatePreventionPlan;

class ValidatePreventionPlanResponse
{
    /** @param array<array{propertyName: string, errors: array<mixed>}> $invalidProperties */
    public function __construct(
        public bool $hasInvalidProperties,
        public array $invalidProperties = [],
    ) {}
}
