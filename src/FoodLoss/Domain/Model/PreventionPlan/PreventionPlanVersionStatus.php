<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

enum PreventionPlanVersionStatus: string
{
    case InProgress = 'InProgress';

    case Loaded = 'Loaded';

    case Configured = 'Configured';

    case Generated = 'Generated';

    case Abandoned = 'Abandoned';

    case Completed = 'Completed';

    public function isClosed(): bool
    {
        return match ($this) {
            self::Completed => true,
            default => false
        };
    }
}
