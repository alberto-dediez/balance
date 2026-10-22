<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;

class NonConsolidatedPropertiesSynchronizer
{
    public function __construct(private NonConsolidatedPropertyEmissionsResolver $resolver) {}

    /** @param list<NonConsolidatedPropertyAnswer>|null $answers */
    public function sync(CompanyCarbonFootprint $companyCarbonFootprint, ?array $answers): void
    {
        $resolved = $this->resolver->resolve($companyCarbonFootprint);

        $resolvedPropertyIds = [];
        foreach ($resolved as $emissions) {
            $resolvedPropertyIds[(string) $emissions->property->id()] = $emissions->property->id();
        }

        $manualByProperty = [];

        if (null === $answers) {
            foreach ($companyCarbonFootprint->nonConsolidatedPropertyLines() as $line) {
                $manualByProperty[(string) $line->propertyId] = $line->manualCo2eq;
            }
        } else {
            foreach ($answers as $answer) {
                if (!isset($resolvedPropertyIds[(string) $answer->propertyId])) {
                    throw new \InvalidArgumentException('Property is not a non-consolidated property of the company');
                }

                $manualByProperty[(string) $answer->propertyId] = $answer->manualCo2eq;
            }
        }

        foreach ($resolved as $emissions) {
            $companyCarbonFootprint->recordNonConsolidatedProperty(
                $emissions->property->id(),
                $emissions->source,
                $emissions->origin?->id(),
                $emissions->calculatedEmissions,
                $manualByProperty[(string) $emissions->property->id()] ?? null,
            );
        }

        $companyCarbonFootprint->retainNonConsolidatedProperties(array_values($resolvedPropertyIds));
    }
}
