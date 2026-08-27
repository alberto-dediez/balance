<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\ContributionCalculator;
use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use PHPUnit\Framework\TestCase;

class ContributionCalculatorTest extends TestCase
{
    public function testPricesTheEmissionsAndSplitsTheManagementFee(): void
    {
        $amounts = $this->createSUT()->fromEmissions(
            co2eqKg: 2000.0,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            countryCode: 'ES',
        );

        $this->assertSame(2.0, $amounts->tonnes);
        $this->assertSame(40.0, $amounts->taxableAmount);
        $this->assertSame(10.0, $amounts->managementFeeAmount);
        $this->assertSame(30.0, $amounts->contributionAmount);
        $this->assertSame(8.4, $amounts->vatAmount);
        $this->assertSame(48.4, $amounts->totalAmount);
        $this->assertFalse($amounts->freeAmount);
    }

    public function testAProjectPricedAtItsCostHasNoManagementFee(): void
    {
        $amounts = $this->createSUT()->fromEmissions(
            co2eqKg: 1000.0,
            pricePerTonne: 20.0,
            costPerTonne: 20.0,
            countryCode: 'ES',
        );

        $this->assertSame(0.0, $amounts->managementFeeAmount);
        $this->assertSame(20.0, $amounts->contributionAmount);
    }

    public function testTheManagementFeeIsNeverNegative(): void
    {
        $amounts = $this->createSUT()->fromEmissions(
            co2eqKg: 1000.0,
            pricePerTonne: 10.0,
            costPerTonne: 15.0,
            countryCode: 'ES',
        );

        $this->assertSame(0.0, $amounts->managementFeeAmount);
        $this->assertSame(10.0, $amounts->contributionAmount);
    }

    public function testAFreeAmountIsConvertedIntoTonnes(): void
    {
        $amounts = $this->createSUT()->fromFreeAmount(
            amount: 50.0,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            countryCode: 'ES',
        );

        $this->assertTrue($amounts->freeAmount);
        $this->assertNull($amounts->co2eqKg);
        $this->assertSame(2.066, $amounts->tonnes);
        $this->assertSame(41.32, $amounts->taxableAmount);
        $this->assertSame(8.68, $amounts->vatAmount);
        $this->assertSame(10.33, $amounts->managementFeeAmount);
        $this->assertSame(50.0, $amounts->totalAmount);
    }

    public function testAFreeAmountIsChargedToTheContributorAsTyped(): void
    {
        foreach (['ES', 'DE'] as $countryCode) {
            $amounts = $this->createSUT()->fromFreeAmount(
                amount: 10.0,
                pricePerTonne: 20.0,
                costPerTonne: 15.0,
                countryCode: $countryCode,
            );

            $this->assertSame(10.0, $amounts->totalAmount);
            $this->assertSame(10.0, round($amounts->taxableAmount + $amounts->vatAmount, 2));
        }
    }

    public function testACompanyOutsideTheHomeCountryPaysNoVat(): void
    {
        $amounts = $this->createSUT()->fromEmissions(
            co2eqKg: 1000.0,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            countryCode: 'DE',
            isCompany: true,
            vatNumber: 'DE123456789',
        );

        $this->assertSame(0.0, $amounts->vatAmount);
        $this->assertSame(20.0, $amounts->totalAmount);
        $this->assertSame(VatExemptionReason::ReverseCharge, $amounts->vatReason);
    }

    public function testRejectsAProjectWithoutAPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->fromEmissions(1000.0, 0.0, 15.0, 'ES');
    }

    public function testRejectsANonPositiveFreeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->fromFreeAmount(0.0, 20.0, 15.0, 'ES');
    }

    public function testRejectsAFreeAmountAboveTheCeiling(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->fromFreeAmount(ContributionCalculator::MAX_FREE_AMOUNT + 0.01, 20.0, 15.0, 'ES');
    }

    public function testRejectsNonPositiveEmissions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()->fromEmissions(0.0, 20.0, 15.0, 'ES');
    }

    private function createSUT(): ContributionCalculator
    {
        return new ContributionCalculator(new VatCalculator(
            new VatNumberFormatValidator(),
            rates: ['ES' => 21.0, 'DE' => 19.0],
            euCountries: ['ES', 'DE'],
            homeCountry: 'ES',
        ));
    }
}
