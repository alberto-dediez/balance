<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

enum ProjectContributionStatus: string
{
    case Pending = 'pending';

    case Paid = 'paid';

    case Failed = 'failed';

    case Expired = 'expired';

    public function isResolved(): bool
    {
        return self::Pending !== $this;
    }
}
