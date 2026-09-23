<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class OrganizationalCarbonFootprintReportLinesBuilder
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityItemNameResolver $itemNameResolver,
    ) {}

    /**
     * @param array<string, string> $propertyNamesById
     *
     * @return list<OrganizationalCarbonFootprintReportActivityLine>
     */
    public function activityLines(CarbonFootprint $carbonFootprint, array $propertyNamesById): array
    {
        $year = $carbonFootprint->calculationYear();
        $emissionFactorGroupId = $carbonFootprint->emissionFactorGroupId();

        $lines = [];

        foreach ($carbonFootprint->activityReferences() as $reference) {
            $activity = $this->activityRepository->findById($reference->activityId);
            $scope = $reference->placement->stage;

            if (null === $activity || !$scope instanceof Scope) {
                continue;
            }

            $propertyId = $reference->placement->propertyId;
            $locationBasedEmissions = $activity instanceof ElectricityActivity ? $activity->locationBasedEmissions() : null;

            $lines[] = new OrganizationalCarbonFootprintReportActivityLine(
                scope: $scope,
                source: $reference->placement->source,
                propertyName: null !== $propertyId ? ($propertyNamesById[(string) $propertyId] ?? (string) $propertyId) : null,
                itemName: $this->itemNameResolver->resolve($activity, $year, $emissionFactorGroupId),
                activityType: ActivityType::fromActivity($activity),
                quantity: $activity->quantity()->value(),
                unit: $activity->quantity()->unit(),
                factorCo2eq: $activity->emissionFactor()->co2eq(),
                factorUnit: $activity->emissionFactor()->unit(),
                factorYear: $activity->emissionFactor()->year(),
                factorSource: $activity->emissionFactor()->source(),
                emissionsCo2eq: $activity->emissions()->co2eq(),
                locationBasedEmissionsCo2eq: $locationBasedEmissions?->co2eq(),
            );
        }

        usort($lines, static fn(OrganizationalCarbonFootprintReportActivityLine $a, OrganizationalCarbonFootprintReportActivityLine $b): int => [
            $a->scope->stepNumber(),
            self::sourcePosition($a->source),
            $a->propertyName ?? '',
            $a->itemName,
        ] <=> [
            $b->scope->stepNumber(),
            self::sourcePosition($b->source),
            $b->propertyName ?? '',
            $b->itemName,
        ]);

        return $lines;
    }

    /** @return list<OrganizationalCarbonFootprintReportExclusionLine> */
    public function exclusionLines(CarbonFootprint $carbonFootprint): array
    {
        $lines = [];

        foreach ($carbonFootprint->allExclusions() as $exclusion) {
            $scope = $exclusion->stage();

            if (!$scope instanceof Scope) {
                continue;
            }

            $lines[] = new OrganizationalCarbonFootprintReportExclusionLine(
                scope: $scope,
                source: $exclusion->source(),
                reason: $exclusion->reason(),
                comment: $exclusion->comment(),
            );
        }

        return $lines;
    }

    private static function sourcePosition(Source $source): int
    {
        return (int) array_search($source, Source::cases(), true);
    }
}
