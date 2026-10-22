<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ActivityItemNameResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\PropertyRepository;

final class MitigationEmissionSourcesBuilder
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityItemNameResolver $itemNameResolver,
        private DerivedSourceEmissionsCalculator $derivedSourceEmissionsCalculator,
        private PropertyRepository $propertyRepository,
    ) {}

    /** @return list<MitigationEmissionSource> */
    public function build(CarbonFootprint $carbonFootprint): array
    {
        /** @var array<string, ActivityPlacement> $placements */
        $placements = [];
        $activityIds = [];
        foreach ($carbonFootprint->activityReferences() as $reference) {
            $activityIds[] = $reference->activityId;
            $placements[(string) $reference->activityId] = $reference->placement;
        }

        /** @var array<string, array{scope: Scope, placement: ActivityPlacement, itemId: string, itemName: string, emissions: float}> $groups */
        $groups = [];
        /** @var array<string, string> $groupKeyByActivityId */
        $groupKeyByActivityId = [];

        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $placement = $placements[(string) $activity->id()] ?? null;

            if (null === $placement) {
                continue;
            }

            $scope = Scope::ofSource($placement->source);

            if (null === $scope) {
                continue;
            }

            $itemId = $this->itemNameResolver->itemId($activity) ?? '';
            $key = $placement->source->value . '|' . $itemId;
            $groupKeyByActivityId[(string) $activity->id()] = $key;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'scope' => $scope,
                    'placement' => $placement,
                    'itemId' => $itemId,
                    'itemName' => $this->itemNameResolver->resolve($activity, $carbonFootprint->calculationYear(), $carbonFootprint->emissionFactorGroupId()),
                    'emissions' => 0.0,
                ];
            }

            $groups[$key]['emissions'] += (float) $activity->emissions()->co2eq();
        }

        $emissionSources = [];

        foreach ($groups as $group) {
            if ($group['emissions'] <= 0.0) {
                continue;
            }

            $emissionSources[] = new MitigationEmissionSource(
                scope: $group['scope'],
                source: $group['placement']->source,
                itemId: $group['itemId'],
                itemName: $group['itemName'],
                emissions: $group['emissions'],
            );
        }

        foreach ($this->derivedSourceEmissionsCalculator->nonConsolidatedPropertyLines($carbonFootprint, Scope::Scope3) as $line) {
            $emissions = (float) $line->emissions->co2eq();

            if ($emissions <= 0.0) {
                continue;
            }

            $emissionSources[] = new MitigationEmissionSource(
                scope: Scope::Scope3,
                source: $line->source,
                itemId: (string) $line->propertyId,
                itemName: $this->propertyRepository->find($line->propertyId)?->name() ?? (string) $line->propertyId,
                emissions: $emissions,
            );
        }

        return array_merge($emissionSources, $this->derivedEmissionSources($carbonFootprint, $groups, $groupKeyByActivityId));
    }

    /**
     * @param array<string, array{scope: Scope, placement: ActivityPlacement, itemId: string, itemName: string, emissions: float}> $groups
     * @param array<string, string>                                                                                                $groupKeyByActivityId
     *
     * @return list<MitigationEmissionSource>
     */
    private function derivedEmissionSources(CarbonFootprint $carbonFootprint, array $groups, array $groupKeyByActivityId): array
    {
        /** @var array<string, array{source: Source, originKey: string, itemId: string, itemName: string, emissions: float}> $derivedGroups */
        $derivedGroups = [];

        foreach ($this->derivedSourceEmissionsCalculator->lines($carbonFootprint, Scope::Scope3) as $line) {
            $originKey = $groupKeyByActivityId[(string) $line->activityId] ?? null;

            if (null === $originKey) {
                continue;
            }

            $key = $line->source->value . '|' . $originKey;

            if (!isset($derivedGroups[$key])) {
                $derivedGroups[$key] = [
                    'source' => $line->source,
                    'originKey' => $originKey,
                    'itemId' => $groups[$originKey]['itemId'],
                    'itemName' => $groups[$originKey]['itemName'],
                    'emissions' => 0.0,
                ];
            }

            $derivedGroups[$key]['emissions'] += (float) $line->emissions->co2eq();
        }

        $emissionSources = [];

        foreach ($derivedGroups as $derivedGroup) {
            if ($derivedGroup['emissions'] <= 0.0) {
                continue;
            }

            $emissionSources[] = new MitigationEmissionSource(
                scope: Scope::Scope3,
                source: $derivedGroup['source'],
                itemId: $derivedGroup['itemId'],
                itemName: $derivedGroup['itemName'],
                emissions: $derivedGroup['emissions'],
                originKey: $derivedGroup['originKey'],
            );
        }

        return $emissionSources;
    }
}
