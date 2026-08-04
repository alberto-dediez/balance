<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Reject;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Application\CommandBus\CommandResponse;

class RejectCreditPurchaseResponse implements CommandResponse
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
    ) {}
}
