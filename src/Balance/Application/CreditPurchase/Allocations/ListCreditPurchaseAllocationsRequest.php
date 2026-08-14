<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Allocations;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;

class ListCreditPurchaseAllocationsRequest
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
    ) {}
}
