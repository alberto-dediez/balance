<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

final class VatResult
{
    public function __construct(
        public readonly float $rate,
        public readonly float $amount,
        public readonly VatExemptionReason $reason,
    ) {}

    public function isExempt(): bool
    {
        return 0.0 === $this->rate;
    }
}
