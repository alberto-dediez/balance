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

    case DraftApproved = 'DraftApproved';

    case Abandoned = 'Abandoned';

    case Completed = 'Completed';

    public function isValidated(): bool
    {
        return match ($this) {
            self::Validated, self::Loaded, self::Configured, self::Generated, self::DraftApproved => true,
            default => false
        };
    }

    public function isLoaded(): bool
    {
        return match ($this) {
            self::Loaded, self::Configured, self::Generated, self::DraftApproved => true,
            default => false
        };
    }

    public function isGenerated(): bool
    {
        return match ($this) {
            self::Generated, self::DraftApproved => true,
            default => false
        };
    }

    public function isDraftApproved(): bool
    {
        return match ($this) {
            self::DraftApproved => true,
            default => false
        };
    }

    public function isClosed(): bool
    {
        return match ($this) {
            self::Completed => true,
            default => false
        };
    }

    public function stepActive(): PreventionPlanStep
    {
        if ($this->isDraftApproved()) {
            return PreventionPlanStep::Publish;
        }

        if ($this->isGenerated()) {
            return PreventionPlanStep::Draft;
        }

        if ($this->isLoaded()) {
            return PreventionPlanStep::Actions;
        }

        if ($this->isValidated()) {
            return PreventionPlanStep::Validate;
        }

        return PreventionPlanStep::General;
    }

    public function style(): string
    {
        return match ($this) {
            self::InProgress => 'bg-light text-dark',
            self::Validated => 'bg-warning',
            self::Loaded => 'bg-dark',
            self::Configured => 'bg-info',
            self::Generated => 'bg-primary',
            self::DraftApproved => 'bg-secondary',
            self::Abandoned => 'bg-danger',
            self::Completed => 'bg-success',
        };
    }
}
