<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Payments\Domain\Model\Vat\VatExemptionReason;

final class CreditPurchaseQuote
{
    public function __construct(
        private readonly float $taxableAmount,
        private readonly float $vatRate,
        private readonly float $vatAmount,
        private readonly VatExemptionReason $vatReason,
    ) {
        if ($taxableAmount < 0.0 || $vatRate < 0.0 || $vatAmount < 0.0) {
            throw new \InvalidArgumentException('Credit purchase quote amounts cannot be negative.');
        }
    }

    public function taxableAmount(): float
    {
        return $this->taxableAmount;
    }

    public function vatRate(): float
    {
        return $this->vatRate;
    }

    public function vatAmount(): float
    {
        return $this->vatAmount;
    }

    public function vatReason(): VatExemptionReason
    {
        return $this->vatReason;
    }

    public function totalAmount(): float
    {
        return round($this->taxableAmount + $this->vatAmount, 2);
    }

    public function matchesTotal(float $total): bool
    {
        return abs($this->totalAmount() - $total) < 0.005;
    }
}
