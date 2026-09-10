<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Application\CommandBus\CommandResponse;

class TransferCreditPurchaseResponse implements CommandResponse
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
        public CreditPurchaseId $destinationCreditPurchaseId,
        public float $transferredTonnes,
        public ?float $transferredAmount,
        public float $absorbedTonnes,
    ) {}
}
