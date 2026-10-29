<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Shared\Application\CommandBus\CommandResponse;

class TransferCreditPurchaseResponse implements CommandResponse
{
    /** @param list<TransferredLot> $lots */
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
        public array $lots,
        public float $transferredTonnes,
        public ?float $transferredAmount,
        public float $tonnesPerAccount,
        public float $retainedTonnes,
        public float $lastAccountExtraTonnes,
        public float $absorbedTonnes,
    ) {}
}
