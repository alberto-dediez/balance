<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEventCarbonFootprintRepository implements EventCarbonFootprintRepository
{
    /** @param array<string, EventCarbonFootprint> $eventCarbonFootprints */
    public function __construct(
        private array $eventCarbonFootprints = [],
    ) {}

    public function add(EventCarbonFootprint $eventCarbonFootprint): void
    {
        if (isset($this->eventCarbonFootprints[(string) $eventCarbonFootprint->id()])) {
            return;
        }

        $this->eventCarbonFootprints[(string) $eventCarbonFootprint->id()] = $eventCarbonFootprint;
    }

    public function remove(EventCarbonFootprint $eventCarbonFootprint): void
    {
        if (!isset($this->eventCarbonFootprints[(string) $eventCarbonFootprint->id()])) {
            return;
        }

        unset($this->eventCarbonFootprints[(string) $eventCarbonFootprint->id()]);
    }

    public function find(EventCarbonFootprintId $eventCarbonFootprintId): ?EventCarbonFootprint
    {
        return (isset($this->eventCarbonFootprints[(string) $eventCarbonFootprintId]) && false == $this->eventCarbonFootprints[(string) $eventCarbonFootprintId]->isRemoved())
            ? $this->eventCarbonFootprints[(string) $eventCarbonFootprintId]
            : null
        ;
    }

    public function findByActivityId(ActivityId $activityId): ?EventCarbonFootprint
    {
        foreach ($this->eventCarbonFootprints as $eventCarbonFootprint) {
            if (true == $eventCarbonFootprint->isRemoved()) {
                continue;
            }

            if (null !== $eventCarbonFootprint->activities()->forActivityId($activityId)) {
                return $eventCarbonFootprint;
            }
        }

        return null;
    }

    public function findByEventId(EventId $eventId): ?EventCarbonFootprint
    {
        foreach ($this->eventCarbonFootprints as $eventCarbonFootprint) {
            if (true == $eventCarbonFootprint->isRemoved()) {
                continue;
            }

            $subjectEventId = $eventCarbonFootprint->eventId();

            if (null !== $subjectEventId && $subjectEventId->equals($eventId)) {
                return $eventCarbonFootprint;
            }
        }

        return null;
    }

    /** @return array<int, EventCarbonFootprint> */
    public function findByFilter(EventCarbonFootprintFilter $filter): array
    {
        $results = [];

        if (0 === count($this->eventCarbonFootprints)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->eventCarbonFootprints as $eventCarbonFootprint) {
            if ($filter->eventId instanceof EventId && (null === $eventCarbonFootprint->eventId() || !$filter->eventId->equals($eventCarbonFootprint->eventId()))) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null === $eventCarbonFootprint->accountId() || !$filter->accountId->equals($eventCarbonFootprint->accountId()))) {
                continue;
            }

            if (true == $eventCarbonFootprint->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $eventCarbonFootprint;
        }

        return $results;
    }

    public function totalResultsByFilter(EventCarbonFootprintFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
