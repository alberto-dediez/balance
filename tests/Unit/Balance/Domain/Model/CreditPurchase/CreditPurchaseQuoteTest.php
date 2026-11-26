<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseQuote;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use PHPUnit\Framework\TestCase;

class CreditPurchaseQuoteTest extends TestCase
{
    public function testTotalAmountAddsTheVatToTheTaxableAmount(): void
    {
        $quote = new CreditPurchaseQuote(30.88, 21.0, 6.48, VatExemptionReason::Standard);

        $this->assertSame(37.36, $quote->totalAmount());
    }

    public function testMatchesTheSameTotal(): void
    {
        $this->assertTrue(CreditPurchaseQuoteMother::standard()->matchesTotal(181.5));
    }

    public function testMatchesATotalWithARoundingDifference(): void
    {
        $this->assertTrue(CreditPurchaseQuoteMother::standard()->matchesTotal(181.501));
    }

    public function testDoesNotMatchADifferentTotal(): void
    {
        $this->assertFalse(CreditPurchaseQuoteMother::standard()->matchesTotal(181.49));
    }

    public function testItThrowsWithANegativeTaxableAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CreditPurchaseQuote(-1.0, 21.0, 0.0, VatExemptionReason::Standard);
    }

    public function testItThrowsWithANegativeVatRate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CreditPurchaseQuote(100.0, -21.0, 0.0, VatExemptionReason::Standard);
    }

    public function testItThrowsWithANegativeVatAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CreditPurchaseQuote(100.0, 21.0, -21.0, VatExemptionReason::Standard);
    }
}
