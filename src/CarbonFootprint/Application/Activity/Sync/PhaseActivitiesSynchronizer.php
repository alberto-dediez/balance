<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\Security\Domain\Model\User\User;

final class PhaseActivitiesSynchronizer
{
    public function __construct(private ActivityRowHandlerRegistry $handlers) {}

    /** @param list<ActivityRow> $rows */
    public function sync(EventCarbonFootprint $eventCarbonFootprint, Phase $phase, Source $source, array $rows, User $user): void
    {
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId()
            ?? throw new \InvalidArgumentException(sprintf('EventCarbonFootprint "%s" has no emission factor group configured.', $eventCarbonFootprint->id()));

        $year = $eventCarbonFootprint->calculationYear();

        $existingIds = $eventCarbonFootprint->activities()
            ->filterByPhase($phase)
            ->filterBySource($source)
            ->activityIds();

        $keptIds = [];

        foreach ($rows as $row) {
            $handler = $this->handlers->forActivityType($row->activityType);

            $activityId = $row->activityId;

            if (null !== $activityId && self::contains($existingIds, $activityId)) {
                $handler->update($activityId, $row, $year, $emissionFactorGroupId, $user);
                $keptIds[] = $activityId;

                continue;
            }

            $activityId = $handler->register($row, $year, $emissionFactorGroupId, $user);
            $eventCarbonFootprint->addActivity($activityId, $phase, $source);
            $keptIds[] = $activityId;
        }

        foreach ($existingIds as $existingId) {
            if (!self::contains($keptIds, $existingId)) {
                $eventCarbonFootprint->removeActivity($existingId);
            }
        }
    }

    /** @param ActivityId[] $ids */
    private static function contains(array $ids, ActivityId $id): bool
    {
        foreach ($ids as $candidate) {
            if ($candidate->equals($id)) {
                return true;
            }
        }

        return false;
    }
}
