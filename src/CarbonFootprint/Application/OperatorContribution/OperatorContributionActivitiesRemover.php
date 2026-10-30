<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;

final class OperatorContributionActivitiesRemover
{
    public function __construct(private ActivityRepository $activityRepository) {}

    public function removeAll(OperatorContribution $contribution): void
    {
        foreach ($contribution->activityReferences() as $reference) {
            $activity = $this->activityRepository->findById($reference->activityId);

            if (null === $activity) {
                continue;
            }

            $activity->remove();
            $this->activityRepository->remove($activity);
        }

        $contribution->removeAllActivities();
    }

    /**
     * @param list<ActivityReference> $before
     */
    public function removeDetached(array $before, OperatorContribution $contribution): void
    {
        $current = $contribution->activityReferences();

        foreach ($before as $reference) {
            foreach ($current as $kept) {
                if ($kept->activityId->equals($reference->activityId)) {
                    continue 2;
                }
            }

            $activity = $this->activityRepository->findById($reference->activityId);

            if (null === $activity) {
                continue;
            }

            $activity->remove();
            $this->activityRepository->remove($activity);
        }
    }
}
