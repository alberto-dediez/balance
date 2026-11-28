<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

enum ContestEntryStatus: string
{
    case PendingPayment = 'pending_payment';

    case Confirmed = 'confirmed';

    case Disqualified = 'disqualified';

    public function style(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-warning text-dark',
            self::Confirmed => 'bg-success',
            self::Disqualified => 'bg-secondary',
        };
    }
}
