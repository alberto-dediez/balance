<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

final class VatCalculator
{
    /**
     * @param array<string, float> $rates
     * @param array<int, string>   $euCountries
     */
    public function __construct(
        private VatNumberFormatValidator $vatNumberFormatValidator,
        private array $rates = [],
        private array $euCountries = [],
        private string $homeCountry = 'ES',
    ) {}

    public function calculate(
        float $taxableAmount,
        string $countryCode,
        bool $isCompany = false,
        ?string $vatNumber = null,
    ): VatResult {
        $country = strtoupper(trim($countryCode));
        $reason = $this->reasonFor($country, $isCompany, $vatNumber);
        $rate = VatExemptionReason::ReverseCharge === $reason || VatExemptionReason::OutsideEu === $reason
            ? 0.0
            : $this->rateFor($country);

        return new VatResult(
            rate: $rate,
            amount: round($taxableAmount * $rate / 100, 2),
            reason: $reason,
        );
    }

    private function reasonFor(string $country, bool $isCompany, ?string $vatNumber): VatExemptionReason
    {
        if (!$this->isEuCountry($country)) {
            return VatExemptionReason::OutsideEu;
        }

        if ($this->isHomeCountry($country)) {
            return VatExemptionReason::Standard;
        }

        if ($isCompany && $this->vatNumberFormatValidator->isValid($vatNumber, $country)) {
            return VatExemptionReason::ReverseCharge;
        }

        return VatExemptionReason::OssDestination;
    }

    private function rateFor(string $country): float
    {
        return $this->rates[$country] ?? 0.0;
    }

    private function isHomeCountry(string $country): bool
    {
        return strtoupper($this->homeCountry) === $country;
    }

    private function isEuCountry(string $country): bool
    {
        return in_array($country, array_map(strtoupper(...), $this->euCountries), true);
    }
}
