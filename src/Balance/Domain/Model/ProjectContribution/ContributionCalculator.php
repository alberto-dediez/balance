<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatResult;

final class ContributionCalculator
{
    public const MAX_FREE_AMOUNT = 999999.99;

    public const MIN_TOTAL_AMOUNT = 0.50;

    public function __construct(private VatCalculator $vatCalculator) {}

    public function fromEmissions(
        float $co2eqKg,
        float $pricePerTonne,
        float $costPerTonne,
        string $countryCode,
        bool $isCompany = false,
        ?string $vatNumber = null,
    ): ContributionAmounts {
        $this->assertPrice($pricePerTonne);

        if ($co2eqKg <= 0.0) {
            throw new \InvalidArgumentException('Emissions to offset must be greater than zero.');
        }

        $tonnes = $co2eqKg / 1000;
        $taxableAmount = round($tonnes * $pricePerTonne, 2);

        return $this->build(
            co2eqKg: $co2eqKg,
            tonnes: $tonnes,
            taxableAmount: $taxableAmount,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $costPerTonne,
            freeAmount: false,
            vat: $this->vatCalculator->calculate($taxableAmount, $countryCode, $isCompany, $vatNumber),
        );
    }

    public function fromFreeAmount(
        float $amount,
        float $pricePerTonne,
        float $costPerTonne,
        string $countryCode,
        bool $isCompany = false,
        ?string $vatNumber = null,
    ): ContributionAmounts {
        $this->assertPrice($pricePerTonne);

        if (!is_finite($amount) || $amount <= 0.0) {
            throw new \InvalidArgumentException('The contribution amount must be greater than zero.');
        }

        if ($amount > self::MAX_FREE_AMOUNT) {
            throw new \InvalidArgumentException(sprintf('The contribution amount cannot exceed %s.', self::MAX_FREE_AMOUNT));
        }

        $totalAmount = round($amount, 2);
        $vat = $this->vatCalculator->extractFromTotal($totalAmount, $countryCode, $isCompany, $vatNumber);
        $taxableAmount = round($totalAmount - $vat->amount, 2);

        return $this->build(
            co2eqKg: null,
            tonnes: $taxableAmount / $pricePerTonne,
            taxableAmount: $taxableAmount,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $costPerTonne,
            freeAmount: true,
            vat: $vat,
        );
    }

    private function build(
        ?float $co2eqKg,
        float $tonnes,
        float $taxableAmount,
        float $pricePerTonne,
        float $costPerTonne,
        bool $freeAmount,
        VatResult $vat,
    ): ContributionAmounts {
        $managementFeeAmount = $this->managementFee($tonnes, $pricePerTonne, $costPerTonne, $taxableAmount);

        return new ContributionAmounts(
            co2eqKg: $co2eqKg,
            tonnes: $tonnes,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $costPerTonne,
            freeAmount: $freeAmount,
            contributionAmount: round($taxableAmount - $managementFeeAmount, 2),
            managementFeeAmount: $managementFeeAmount,
            taxableAmount: $taxableAmount,
            vatRate: $vat->rate,
            vatAmount: $vat->amount,
            totalAmount: round($taxableAmount + $vat->amount, 2),
            vatReason: $vat->reason,
        );
    }

    private function managementFee(
        float $tonnes,
        float $pricePerTonne,
        float $costPerTonne,
        float $taxableAmount,
    ): float {
        $fee = round($tonnes * ($pricePerTonne - $costPerTonne), 2);

        return max(0.0, min($fee, $taxableAmount));
    }

    private function assertPrice(float $pricePerTonne): void
    {
        if ($pricePerTonne <= 0.0) {
            throw new \InvalidArgumentException('The project price per tonne must be greater than zero.');
        }
    }
}
