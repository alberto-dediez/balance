<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

enum PayoutStatus: string
{
    case Draft = 'draft';

    case Sent = 'sent';

    case Paid = 'paid';

    case Failed = 'failed';

    case Cancelled = 'cancelled';

    public function isResolved(): bool
    {
        return self::Paid === $this || self::Cancelled === $this;
    }

    public function isPending(): bool
    {
        return self::Draft === $this || self::Sent === $this;
    }
}
