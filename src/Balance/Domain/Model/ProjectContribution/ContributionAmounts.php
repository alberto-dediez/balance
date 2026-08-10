<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Payments\Domain\Model\Vat\VatExemptionReason;

final class ContributionAmounts
{
    public function __construct(
        public readonly ?float $co2eqKg,
        public readonly float $tonnes,
        public readonly float $pricePerTonne,
        public readonly float $costPerTonne,
        public readonly bool $freeAmount,
        public readonly float $contributionAmount,
        public readonly float $managementFeeAmount,
        public readonly float $taxableAmount,
        public readonly float $vatRate,
        public readonly float $vatAmount,
        public readonly float $totalAmount,
        public readonly VatExemptionReason $vatReason,
        public readonly string $currency = 'EUR',
    ) {}
}
