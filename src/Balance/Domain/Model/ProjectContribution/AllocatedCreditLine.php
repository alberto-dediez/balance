<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;

/** One credit lot consumed by a contribution, in the order the FIFO assignment walked the lots. */
final readonly class AllocatedCreditLine
{
    public function __construct(
        public int $position,
        public CreditPurchaseId $creditPurchaseId,
        public ?string $purchaseReference,
        public float $tonnes,
    ) {}
}
