<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

enum PaymentStatus: string
{
    case Pending = 'pending';

    case Paid = 'paid';

    case Failed = 'failed';

    case Expired = 'expired';

    case Cancelled = 'cancelled';

    public function isResolved(): bool
    {
        return self::Pending !== $this;
    }
}
