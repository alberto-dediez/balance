<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

final class IntensityIndicatorsCalculator
{
    /** @return list<IntensityIndicatorValue> */
    public function calculate(float $totalKgCo2eq, IntensityDenominators $denominators): array
    {
        $values = [];

        foreach (IntensityIndicator::cases() as $indicator) {
            $denominator = $denominators->denominatorFor($indicator);

            if (null === $denominator || $denominator <= 0.0) {
                continue;
            }

            $values[] = new IntensityIndicatorValue(
                indicator: $indicator,
                denominator: $denominator,
                kgCo2eqPerUnit: self::intensity($totalKgCo2eq, $denominator),
            );
        }

        return $values;
    }

    public static function intensity(float $totalKgCo2eq, float $denominator): float
    {
        $intensity = $totalKgCo2eq / $denominator;

        return round($intensity, abs($intensity) < 1 ? 4 : 2);
    }
}
