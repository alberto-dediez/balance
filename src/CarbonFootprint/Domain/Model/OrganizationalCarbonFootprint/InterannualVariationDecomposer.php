<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

final class InterannualVariationDecomposer
{
    /**
     * @param list<InventoryLine> $fromLines
     * @param list<InventoryLine> $toLines
     */
    public function decompose(
        int $fromYear,
        float $fromTotal,
        array $fromLines,
        int $toYear,
        float $toTotal,
        array $toLines,
        ?IntensityIndicator $primary,
        ?float $fromDenominator,
        ?float $toDenominator,
    ): InterannualVariation {
        $delta = $toTotal - $fromTotal;

        $activityEffect = null;
        if (null !== $primary && null !== $fromDenominator && null !== $toDenominator && $fromDenominator > 0.0) {
            $activityEffect = ($toDenominator - $fromDenominator) * $fromTotal / $fromDenominator;
        }

        $fromFactors = $this->aggregate($fromLines);
        $toFactors = $this->aggregate($toLines);

        $factorEffect = 0.0;
        foreach ($toFactors as $key => [$toQuantity, $toFactor]) {
            if (!isset($fromFactors[$key])) {
                continue;
            }

            $factorEffect += $toQuantity * ($toFactor - $fromFactors[$key][1]);
        }

        return new InterannualVariation(
            fromYear: $fromYear,
            toYear: $toYear,
            fromTotal: $fromTotal,
            toTotal: $toTotal,
            delta: $delta,
            activityEffect: $activityEffect,
            factorEffect: $factorEffect,
            efficiencyEffect: $delta - ($activityEffect ?? 0.0) - $factorEffect,
            primaryIndicator: $primary,
        );
    }

    /**
     * @param list<InventoryLine> $lines
     *
     * @return array<string, array{0: float, 1: float}>
     */
    private function aggregate(array $lines): array
    {
        $quantities = [];
        $weightedFactors = [];

        foreach ($lines as $line) {
            if (null === $line->quantity || null === $line->factorCo2eq) {
                continue;
            }

            $key = $line->matchingKey();
            $quantities[$key] = ($quantities[$key] ?? 0.0) + $line->quantity;
            $weightedFactors[$key] = ($weightedFactors[$key] ?? 0.0) + $line->quantity * $line->factorCo2eq;
        }

        $aggregated = [];
        foreach ($quantities as $key => $quantity) {
            if (0.0 === $quantity) {
                continue;
            }

            $aggregated[$key] = [$quantity, $weightedFactors[$key] / $quantity];
        }

        return $aggregated;
    }
}
