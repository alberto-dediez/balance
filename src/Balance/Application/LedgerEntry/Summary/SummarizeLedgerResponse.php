<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Summary;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;

final class SummarizeLedgerResponse
{
    /**
     * @param array<string, float> $totalsByType
     * @param array<string, float> $totalsByBeneficiary
     * @param array<string, float> $pendingByBeneficiary
     */
    public function __construct(
        public readonly array $totalsByType = [],
        public readonly array $totalsByBeneficiary = [],
        public readonly array $pendingByBeneficiary = [],
    ) {}

    public function totalFor(LedgerEntryType $type): float
    {
        return $this->totalsByType[$type->value] ?? 0.0;
    }

    public function accruedFor(LedgerBeneficiary $beneficiary): float
    {
        return $this->totalsByBeneficiary[$beneficiary->value] ?? 0.0;
    }

    public function pendingFor(LedgerBeneficiary $beneficiary): float
    {
        return $this->pendingByBeneficiary[$beneficiary->value] ?? 0.0;
    }

    public function collectedAmount(): float
    {
        return round(array_sum($this->totalsByType), 2);
    }
}
