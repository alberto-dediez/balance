<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Persistence\InMemory\Repository;

use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEventRepository implements EventRepository
{
    /** @param array<string, Event> $events */
    public function __construct(
        private array $events = [],
    ) {}

    public function add(Event $event): void
    {
        if (isset($this->events[(string) $event->id()])) {
            return;
        }

        $this->events[(string) $event->id()] = $event;
    }

    public function remove(Event $event): void
    {
        if (!isset($this->events[(string) $event->id()])) {
            return;
        }

        unset($this->events[(string) $event->id()]);
    }

    public function find(EventId $eventId): ?Event
    {
        return (isset($this->events[(string) $eventId]) && false == $this->events[(string) $eventId]->isRemoved())
            ? $this->events[(string) $eventId]
            : null
        ;
    }

    /** @return array<int, Event> */
    public function findByFilter(EventFilter $filter): array
    {
        $results = [];

        if (0 === count($this->events)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->events as $event) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $event->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null === $event->accountId() || !$filter->accountId->equals($event->accountId()))) {
                continue;
            }

            if ($filter->location instanceof DestinationId && (null === $event->location() || !$filter->location->equals($event->location()))) {
                continue;
            }

            if ($filter->size instanceof EventSize && $filter->size !== $event->size()) {
                continue;
            }

            if ($filter->sector instanceof EventSector && $filter->sector !== $event->sector()) {
                continue;
            }

            if ($filter->typology instanceof EventTypology && $filter->typology !== $event->typology()) {
                continue;
            }

            if (true == $event->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $event;
        }

        return $results;
    }

    public function totalResultsByFilter(EventFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
