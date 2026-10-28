<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;

final class ProviderContributionEmissionsCalculator
{
    public function __construct(private ActivityRepository $activityRepository) {}

    /** @return array<string, Emissions> */
    public function emissionsBySource(CarbonFootprint $carbonFootprint, OrganizationalProviderContribution $contribution): array
    {
        $emissionsBySource = [];
        foreach ($contribution->sources() as $source) {
            $emissionsBySource[$source->value] = new Emissions();
        }

        $providerId = $contribution->providerId();

        if (null === $providerId || $contribution->status()->isIgnored()) {
            return $emissionsBySource;
        }

        $propertyId = $contribution->propertyId();

        /** @var array<string, string> $sourceByActivityId */
        $sourceByActivityId = [];
        /** @var list<ActivityId> $activityIds */
        $activityIds = [];

        foreach ($carbonFootprint->activityReferences() as $reference) {
            $placement = $reference->placement;

            if ($placement->stage->key() !== Scope::Scope3->key() || !isset($emissionsBySource[$placement->source->value])) {
                continue;
            }

            if (null === $placement->providerId || !$placement->providerId->equals($providerId)) {
                continue;
            }

            if ((null === $propertyId) !== (null === $placement->propertyId)) {
                continue;
            }

            if (null !== $propertyId && null !== $placement->propertyId && !$propertyId->equals($placement->propertyId)) {
                continue;
            }

            $sourceByActivityId[(string) $reference->activityId] = $placement->source->value;
            $activityIds[] = $reference->activityId;
        }

        if ([] === $activityIds) {
            return $emissionsBySource;
        }

        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $source = $sourceByActivityId[(string) $activity->id()] ?? null;

            if (null === $source) {
                continue;
            }

            $emissionsBySource[$source] = $emissionsBySource[$source]->add($activity->emissions());
        }

        return $emissionsBySource;
    }

    /** @param array<string, Emissions> $emissionsBySource */
    public function total(array $emissionsBySource): Emissions
    {
        $total = new Emissions();
        foreach ($emissionsBySource as $emissions) {
            $total = $total->add($emissions);
        }

        return $total;
    }
}
