<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Series;

final class ListLedgerSeriesResponse
{
    /**
     * @param list<string>                           $months
     * @param array<string, list<float>>             $amountsByBeneficiary
     * @param list<float>                            $accruedByMonth
     * @param list<float>                            $settledByMonth
     * @param list<array{id: string, amount: float}> $topPending
     */
    public function __construct(
        public readonly array $months = [],
        public readonly array $amountsByBeneficiary = [],
        public readonly array $accruedByMonth = [],
        public readonly array $settledByMonth = [],
        public readonly array $topPending = [],
    ) {}
}
