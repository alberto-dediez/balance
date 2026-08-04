<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Request;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Application\CommandBus\CommandResponse;

class RequestCreditPurchaseResponse implements CommandResponse
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
    ) {}
}
