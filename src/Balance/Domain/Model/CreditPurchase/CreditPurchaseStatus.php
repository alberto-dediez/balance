<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

enum CreditPurchaseStatus: string
{
    case Pending = 'pending';

    case Validated = 'validated';

    case Rejected = 'rejected';

    case Cancelled = 'cancelled';
}
