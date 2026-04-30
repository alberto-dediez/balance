<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

enum PreventionPlanStep: int
{
    case General = 1;

    case Validate = 2;

    case Actions = 3;

    case Draft = 4;

    case Publish = 5;

    public function routeName(): string
    {
        return match ($this) {
            self::Publish => 'prevention_plans_publish',
            self::Validate => 'prevention_plans_validate',
            self::Actions => 'prevention_plans_actions',
            self::Draft => 'prevention_plans_draft',
            default => 'prevention_plans_general',
        };
    }
}
