<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ActivityItemNameResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class MitigationEmissionSourcesBuilder
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityItemNameResolver $itemNameResolver,
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

        return $emissionSources;
    }
}
