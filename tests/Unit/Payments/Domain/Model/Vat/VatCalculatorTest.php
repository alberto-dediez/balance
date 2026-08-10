<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Vat;

use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    public function testAppliesTheDomesticRateInTheHomeCountry(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'ES');

        $this->assertSame(21.0, $result->rate);
        $this->assertSame(21.0, $result->amount);
        $this->assertSame(VatExemptionReason::Standard, $result->reason);
    }

    public function testAppliesTheDestinationRateToAnEuConsumer(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'FR');

        $this->assertSame(20.0, $result->rate);
        $this->assertSame(20.0, $result->amount);
        $this->assertSame(VatExemptionReason::OssDestination, $result->reason);
    }

    public function testAppliesTheDestinationRateToAnEuCompanyWithoutAValidVatNumber(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'DE', isCompany: true, vatNumber: 'DE123');

        $this->assertSame(19.0, $result->rate);
        $this->assertSame(VatExemptionReason::OssDestination, $result->reason);
    }

    public function testReverseChargesAnEuCompanyWithAValidVatNumber(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'DE', isCompany: true, vatNumber: 'DE123456789');

        $this->assertSame(0.0, $result->rate);
        $this->assertSame(0.0, $result->amount);
        $this->assertSame(VatExemptionReason::ReverseCharge, $result->reason);
        $this->assertTrue($result->isExempt());
    }

    public function testDoesNotChargeVatOutsideTheEu(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'US');

        $this->assertSame(0.0, $result->rate);
        $this->assertSame(VatExemptionReason::OutsideEu, $result->reason);
    }

    public function testHomeCountryCompaniesAreNeverReverseCharged(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'ES', isCompany: true, vatNumber: 'ESB12345678');

        $this->assertSame(21.0, $result->rate);
        $this->assertSame(VatExemptionReason::Standard, $result->reason);
    }

    public function testRoundsTheVatAmountToTwoDecimals(): void
    {
        $result = $this->createSUT()->calculate(33.33, 'ES');

        $this->assertSame(7.0, $result->amount);
    }

    private function createSUT(): VatCalculator
    {
        return new VatCalculator(
            new VatNumberFormatValidator(),
            rates: ['ES' => 21.0, 'FR' => 20.0, 'DE' => 19.0, 'IT' => 22.0],
            euCountries: ['ES', 'FR', 'DE', 'IT', 'PT'],
            homeCountry: 'ES',
        );
    }
}
