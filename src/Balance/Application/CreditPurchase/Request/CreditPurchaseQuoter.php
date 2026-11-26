<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Request;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseQuote;
use App\Balance\Domain\Model\Project\Project;
use App\Payments\Domain\Model\Vat\VatCalculator;

final class CreditPurchaseQuoter
{
    public function __construct(
        private VatCalculator $vatCalculator,
    ) {}

    public function __invoke(Project $project, float $tonnes, string $country, string $taxId): ?CreditPurchaseQuote
    {
        $costPerTonne = $project->costPerTonne();

        if (null === $costPerTonne || $tonnes <= 0.0) {
            return null;
        }

        $taxableAmount = round($tonnes * $costPerTonne, 2);
        $vat = $this->vatCalculator->calculate($taxableAmount, $country, true, $taxId);

        return new CreditPurchaseQuote($taxableAmount, $vat->rate, $vat->amount, $vat->reason);
    }
}
