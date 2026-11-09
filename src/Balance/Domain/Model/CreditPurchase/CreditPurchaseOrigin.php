<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

enum CreditPurchaseOrigin: string
{
    case Requested = 'requested';

    case Assigned = 'assigned';

    case Received = 'received';
}
