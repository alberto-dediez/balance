<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Allocations;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocation;

class ListCreditPurchaseAllocationsResponse
{
    /** @param list<ProjectContributionCreditAllocation> $allocations */
    public function __construct(
        public CreditPurchase $creditPurchase,
        public array $allocations = [],
        public float $allocatedTonnes = 0.0,
        public float $availableTonnes = 0.0,
    ) {}
}
