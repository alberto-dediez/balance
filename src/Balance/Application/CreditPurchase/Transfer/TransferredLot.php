<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Security\Domain\Model\Account\AccountId;

final readonly class TransferredLot
{
    public function __construct(
        public AccountId $destinationAccountId,
        public CreditPurchaseId $destinationCreditPurchaseId,
        public float $tonnes,
        public ?float $amount,
        public float $absorbedTonnes,
    ) {}
}
