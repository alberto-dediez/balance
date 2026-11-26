<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Request;

use App\Balance\Application\CreditPurchase\Request\CreditPurchaseQuoter;
use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatCountry;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use App\Payments\Infrastructure\Vat\InMemory\InMemoryVatCountryProvider;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseQuoterTest extends TestCase
{
    /**
     * @dataProvider quoteProvider
     */
    public function testQuotesTheRequestedTonnes(
        float $tonnes,
        float $costPerTonne,
        string $country,
        string $taxId,
        float $taxableAmount,
        float $vatRate,
        float $vatAmount,
        float $totalAmount,
        VatExemptionReason $vatReason,
    ): void {
        $quote = $this->createSUT()(ProjectMother::contributable(costPerTonne: $costPerTonne), $tonnes, $country, $taxId);

        $this->assertNotNull($quote);
        $this->assertSame($taxableAmount, $quote->taxableAmount());
        $this->assertSame($vatRate, $quote->vatRate());
        $this->assertSame($vatAmount, $quote->vatAmount());
        $this->assertSame($totalAmount, $quote->totalAmount());
        $this->assertSame($vatReason, $quote->vatReason());
    }

    /** @return iterable<string, array{float, float, string, string, float, float, float, float, VatExemptionReason}> */
    public static function quoteProvider(): iterable
    {
        yield 'spanish company' => [10.0, 15.0, 'ESP', 'B12345678', 150.0, 21.0, 31.5, 181.5, VatExemptionReason::Standard];
        yield 'rounded amounts' => [2.5, 12.35, 'ESP', 'B12345678', 30.88, 21.0, 6.48, 37.36, VatExemptionReason::Standard];
        yield 'eu company with a valid vat number' => [10.0, 15.0, 'FRA', 'FR12345678901', 150.0, 0.0, 0.0, 150.0, VatExemptionReason::ReverseCharge];
        yield 'eu company with an invalid vat number' => [10.0, 15.0, 'FRA', 'X', 150.0, 20.0, 30.0, 180.0, VatExemptionReason::OssDestination];
        yield 'outside the eu' => [10.0, 15.0, 'USA', '12-3456789', 150.0, 0.0, 0.0, 150.0, VatExemptionReason::OutsideEu];
        yield 'zero cost per tonne' => [10.0, 0.0, 'ESP', 'B12345678', 0.0, 21.0, 0.0, 0.0, VatExemptionReason::Standard];
    }

    public function testDoesNotQuoteAProjectWithoutCostPerTonne(): void
    {
        $this->assertNull($this->createSUT()(ProjectMother::contributable(costPerTonne: null), 10.0, 'ESP', 'B12345678'));
    }

    public function testDoesNotQuoteZeroTonnes(): void
    {
        $this->assertNull($this->createSUT()(ProjectMother::contributable(costPerTonne: 15.0), 0.0, 'ESP', 'B12345678'));
    }

    private function createSUT(): CreditPurchaseQuoter
    {
        return new CreditPurchaseQuoter(
            new VatCalculator(
                new VatNumberFormatValidator(),
                new InMemoryVatCountryProvider([
                    new VatCountry('ESP', 21.0, true),
                    new VatCountry('FRA', 20.0, true),
                    new VatCountry('USA', 0.0, false),
                ]),
                homeCountry: 'ESP',
            ),
        );
    }
}
