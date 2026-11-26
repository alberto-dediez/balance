<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseQuote;
use App\Payments\Domain\Model\Vat\VatExemptionReason;

class CreditPurchaseQuoteMother
{
    public static function standard(float $taxableAmount = 150.0): CreditPurchaseQuote
    {
        return new CreditPurchaseQuote(
            taxableAmount: $taxableAmount,
            vatRate: 21.0,
            vatAmount: round($taxableAmount * 21.0 / 100, 2),
            vatReason: VatExemptionReason::Standard,
        );
    }
}
