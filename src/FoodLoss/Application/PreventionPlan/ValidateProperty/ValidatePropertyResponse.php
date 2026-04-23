<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidateProperty;

use Symfony\Component\Translation\TranslatableMessage;

class ValidatePropertyResponse
{
    /** @param TranslatableMessage[] $errors */
    public function __construct(
        public bool $hasErrors,
        public string $propertyName = '',
        public array $errors = [],
    ) {}
}
