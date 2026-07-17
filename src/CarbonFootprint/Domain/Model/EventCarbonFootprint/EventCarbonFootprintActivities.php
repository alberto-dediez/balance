<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class EventCarbonFootprintActivities
{
    /** @var Collection<int, EventCarbonFootprintActivity> */
    private Collection $activities;

    /** @param iterable<EventCarbonFootprintActivity> $activities */
    public function __construct(iterable &$activities)
    {
        if (!$activities instanceof Collection) {
            $activities = new ArrayCollection(is_array($activities) ? array_values($activities) : iterator_to_array($activities));
        }
        $this->activities = $activities;
    }

    public function add(EventCarbonFootprintActivity $activity): void
    {
        $this->activities->add($activity);
    }

    public function remove(ActivityId $activityId): void
    {
        $filtered = array_values(array_filter(
            $this->activities->toArray(),
            static fn(EventCarbonFootprintActivity $a) => !$a->activityId()->equals($activityId),
        ));
        $this->activities = new ArrayCollection($filtered);
    }

    public function forActivityId(ActivityId $activityId): ?EventCarbonFootprintActivity
    {
        foreach ($this->activities as $activity) {
            if ($activity->activityId()->equals($activityId)) {
                return $activity;
            }
        }

        return null;
    }

    public function filterByPhase(Phase $phase): self
    {
        $filtered = [];

        foreach ($this->activities as $activity) {
            if ($activity->phase() === $phase) {
                $filtered[] = $activity;
            }
        }

        return new self($filtered);
    }

    public function filterBySource(Source $source): self
    {
        $filtered = [];

        foreach ($this->activities as $activity) {
            if ($activity->source() === $source) {
                $filtered[] = $activity;
            }
        }

        return new self($filtered);
    }

    public function filterByProviderId(?ProviderId $providerId): self
    {
        $filtered = [];

        foreach ($this->activities as $activity) {
            $activityProviderId = $activity->providerId();

            if (null === $providerId ? null === $activityProviderId : ($activityProviderId instanceof ProviderId && $activityProviderId->equals($providerId))) {
                $filtered[] = $activity;
            }
        }

        return new self($filtered);
    }

    public function filterByFromEmployees(bool $fromEmployees): self
    {
        $filtered = [];

        foreach ($this->activities as $activity) {
            if ($activity->fromEmployees() === $fromEmployees) {
                $filtered[] = $activity;
            }
        }

        return new self($filtered);
    }

    /** @return ActivityId[] */
    public function activityIds(): array
    {
        return array_map(
            static fn(EventCarbonFootprintActivity $a) => $a->activityId(),
            $this->activities->toArray(),
        );
    }

    /** @return EventCarbonFootprintActivity[] */
    public function toArray(): array
    {
        return $this->activities->toArray();
    }
}
