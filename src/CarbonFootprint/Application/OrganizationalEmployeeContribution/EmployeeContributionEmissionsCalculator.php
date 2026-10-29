<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class EmployeeContributionEmissionsCalculator
{
    public function __construct(private ActivityRepository $activityRepository) {}

    /** @return array<string, array<string, Emissions>> */
    public function emissionsBySourceAndBlock(CarbonFootprint $carbonFootprint): array
    {
        /** @var array<string, array{source: string, block: string}> $keysByActivityId */
        $keysByActivityId = [];
        /** @var list<ActivityId> $activityIds */
        $activityIds = [];

        foreach ($carbonFootprint->activityReferences() as $reference) {
            $placement = $reference->placement;

            if (!$placement->fromEmployees || $placement->stage->key() !== Scope::Scope3->key()) {
                continue;
            }

            $keysByActivityId[(string) $reference->activityId] = [
                'source' => $placement->source->value,
                'block' => null !== $placement->propertyId ? (string) $placement->propertyId : '',
            ];
            $activityIds[] = $reference->activityId;
        }

        if ([] === $activityIds) {
            return [];
        }

        $emissions = [];

        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $keys = $keysByActivityId[(string) $activity->id()] ?? null;

            if (null === $keys) {
                continue;
            }

            $emissions[$keys['source']][$keys['block']] = ($emissions[$keys['source']][$keys['block']] ?? new Emissions())->add($activity->emissions());
        }

        return $emissions;
    }
}
