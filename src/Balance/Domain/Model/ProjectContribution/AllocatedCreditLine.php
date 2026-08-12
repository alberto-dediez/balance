<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;

final readonly class AllocatedCreditLine
{
    public function __construct(
        public int $position,
        public CreditPurchaseId $creditPurchaseId,
        public ?string $purchaseReference,
        public float $tonnes,
        public float $startTonnes,
    ) {}
}
