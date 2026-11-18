<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

enum PlanStepStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Skipped => 'secondary',
            self::Done => 'success',
            self::Failed => 'danger',
        };
    }
}
