<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

final class LedgerBalance
{
    public function __construct(
        private LedgerBeneficiary $beneficiary,
        private ?string $beneficiaryId,
        private float $accruedAmount = 0.0,
        private float $settledAmount = 0.0,
        private int $entriesCount = 0,
        private string $currency = 'EUR',
    ) {}

    public function beneficiary(): LedgerBeneficiary
    {
        return $this->beneficiary;
    }

    public function beneficiaryId(): ?string
    {
        return $this->beneficiaryId;
    }

    public function accruedAmount(): float
    {
        return $this->accruedAmount;
    }

    public function settledAmount(): float
    {
        return $this->settledAmount;
    }

    public function pendingAmount(): float
    {
        return round($this->accruedAmount - $this->settledAmount, 2);
    }

    public function entriesCount(): int
    {
        return $this->entriesCount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
