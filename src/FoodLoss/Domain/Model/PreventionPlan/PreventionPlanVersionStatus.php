<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

enum PreventionPlanVersionStatus: string
{
    case InProgress = 'InProgress';

    case Validated = 'Validated';

    case Loaded = 'Loaded';

    case Configured = 'Configured';

    case Generated = 'Generated';

    case Abandoned = 'Abandoned';

    case Completed = 'Completed';

    public function isValidated(): bool
    {
        return match ($this) {
            self::InProgress => false,
            default => true
        };
    }

    public function isLoaded(): bool
    {
        return match ($this) {
            self::InProgress, self::Validated => false,
            default => true
        };
    }

    public function isGenerated(): bool
    {
        return match ($this) {
            self::InProgress, self::Validated, self::Loaded, self::Configured => false,
            default => true
        };
    }

    public function isClosed(): bool
    {
        return match ($this) {
            self::Completed => true,
            default => false
        };
    }
}
