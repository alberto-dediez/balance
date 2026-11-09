<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Assign;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Application\CommandBus\CommandResponse;

class AssignCreditPurchaseResponse implements CommandResponse
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
        public bool $validatedPendingRequest,
        public bool $partiallyFulfilled,
        public int $remainingPendingRequests,
    ) {}
}
