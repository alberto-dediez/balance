<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocation;

class ListAccountCreditsResponse
{
    /**
     * @param array<CreditBalance>                                     $balances
     * @param array<string, list<CreditPurchase>>                      $purchasesByProject
     * @param array<string, list<ProjectContributionCreditAllocation>> $allocationsByPurchase
     */
    public function __construct(
        public array $balances = [],
        public array $purchasesByProject = [],
        public array $allocationsByPurchase = [],
    ) {}
}
