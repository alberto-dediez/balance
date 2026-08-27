<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Vat;

use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatCountry;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use App\Payments\Infrastructure\Vat\InMemory\InMemoryVatCountryProvider;
use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    public function testAppliesTheDomesticRateInTheHomeCountry(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'ESP');

        $this->assertSame(21.0, $result->rate);
        $this->assertSame(21.0, $result->amount);
        $this->assertSame(VatExemptionReason::Standard, $result->reason);
    }

    public function testAppliesTheDestinationRateToAnEuConsumer(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'FRA');

        $this->assertSame(20.0, $result->rate);
        $this->assertSame(20.0, $result->amount);
        $this->assertSame(VatExemptionReason::OssDestination, $result->reason);
    }

    public function testAppliesTheDestinationRateToAnEuCompanyWithoutAValidVatNumber(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'DEU', isCompany: true, vatNumber: 'DE123');

        $this->assertSame(19.0, $result->rate);
        $this->assertSame(VatExemptionReason::OssDestination, $result->reason);
    }

    public function testReverseChargesAnEuCompanyWithAValidVatNumber(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'DEU', isCompany: true, vatNumber: 'DE123456789');

        $this->assertSame(0.0, $result->rate);
        $this->assertSame(0.0, $result->amount);
        $this->assertSame(VatExemptionReason::ReverseCharge, $result->reason);
        $this->assertTrue($result->isExempt());
    }

    public function testDoesNotChargeVatOutsideTheEu(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'USA');

        $this->assertSame(0.0, $result->rate);
        $this->assertSame(VatExemptionReason::OutsideEu, $result->reason);
    }

    public function testHomeCountryCompaniesAreNeverReverseCharged(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'ESP', isCompany: true, vatNumber: 'ESB12345678');

        $this->assertSame(21.0, $result->rate);
        $this->assertSame(VatExemptionReason::Standard, $result->reason);
    }

    public function testRoundsTheVatAmountToTwoDecimals(): void
    {
        $result = $this->createSUT()->calculate(33.33, 'ESP');

        $this->assertSame(7.0, $result->amount);
    }

    public function testDoesNotChargeVatToAnEuCountryWithoutARate(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'PRT');

        $this->assertSame(0.0, $result->rate);
        $this->assertSame(0.0, $result->amount);
        $this->assertSame(VatExemptionReason::OssDestination, $result->reason);
    }

    public function testDoesNotChargeVatToACountryThatIsNotRegistered(): void
    {
        $result = $this->createSUT()->calculate(100.0, 'XXX');

        $this->assertSame(0.0, $result->rate);
        $this->assertSame(VatExemptionReason::OutsideEu, $result->reason);
    }

    private function createSUT(): VatCalculator
    {
        return new VatCalculator(
            new VatNumberFormatValidator(),
            new InMemoryVatCountryProvider([
                new VatCountry('ESP', 21.0, true),
                new VatCountry('FRA', 20.0, true),
                new VatCountry('DEU', 19.0, true),
                new VatCountry('ITA', 22.0, true),
                new VatCountry('PRT', 0.0, true),
                new VatCountry('USA', 0.0, false),
            ]),
            homeCountry: 'ESP',
        );
    }
}
