<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

enum CreditPurchasePaymentType: string
{
    case PaidByClient = 'paid_by_client';

    case PaidByBioscore = 'paid_by_bioscore';
}
