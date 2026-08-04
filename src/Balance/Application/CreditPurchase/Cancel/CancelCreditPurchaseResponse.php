<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Cancel;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Application\CommandBus\CommandResponse;

class CancelCreditPurchaseResponse implements CommandResponse
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
    ) {}
}
