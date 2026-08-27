<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

final class VatCalculator
{
    public function __construct(
        private VatNumberFormatValidator $vatNumberFormatValidator,
        private VatCountryProvider $vatCountryProvider,
        private string $homeCountry = 'ESP',
    ) {}

    public function calculate(
        float $taxableAmount,
        string $countryCode,
        bool $isCompany = false,
        ?string $vatNumber = null,
    ): VatResult {
        [$rate, $reason] = $this->rateAndReason($countryCode, $isCompany, $vatNumber);

        return new VatResult(
            rate: $rate,
            amount: round($taxableAmount * $rate / 100, 2),
            reason: $reason,
        );
    }

    public function extractFromTotal(
        float $totalAmount,
        string $countryCode,
        bool $isCompany = false,
        ?string $vatNumber = null,
    ): VatResult {
        [$rate, $reason] = $this->rateAndReason($countryCode, $isCompany, $vatNumber);

        return new VatResult(
            rate: $rate,
            amount: round($totalAmount - $totalAmount / (1 + $rate / 100), 2),
            reason: $reason,
        );
    }

    /** @return array{float, VatExemptionReason} */
    private function rateAndReason(string $countryCode, bool $isCompany, ?string $vatNumber): array
    {
        $country = strtoupper(trim($countryCode));
        $vatCountry = $this->vatCountryProvider->findByIso($country);
        $reason = $this->reasonFor($country, $vatCountry, $isCompany, $vatNumber);

        if (!$vatCountry instanceof VatCountry
            || VatExemptionReason::ReverseCharge === $reason
            || VatExemptionReason::OutsideEu === $reason
        ) {
            return [0.0, $reason];
        }

        return [$vatCountry->rate, $reason];
    }

    private function reasonFor(
        string $country,
        ?VatCountry $vatCountry,
        bool $isCompany,
        ?string $vatNumber,
    ): VatExemptionReason {
        if (!$vatCountry instanceof VatCountry || !$vatCountry->europeanUnion) {
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

    private function isHomeCountry(string $country): bool
    {
        return strtoupper($this->homeCountry) === $country;
    }
}
