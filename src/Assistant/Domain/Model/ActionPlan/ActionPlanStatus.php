<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

enum ActionPlanStatus: string
{
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Executing = 'executing';
    case Executed = 'executed';
    case Failed = 'failed';
    case Discarded = 'discarded';
    case Expired = 'expired';

    public function color(): string
    {
        return match ($this) {
            self::Proposed => 'info',
            self::Approved => 'primary',
            self::Executing => 'warning',
            self::Executed => 'success',
            self::Failed => 'danger',
            self::Discarded, self::Expired => 'secondary',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Executed, self::Failed, self::Discarded, self::Expired], true);
    }

    /** @return list<self> */
    public static function active(): array
    {
        return [self::Approved, self::Executing];
    }
}
