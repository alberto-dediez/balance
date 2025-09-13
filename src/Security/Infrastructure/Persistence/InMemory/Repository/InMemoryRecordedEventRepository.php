<?php

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryRecordedEventRepository implements RecordedEventRepository
{
    public function __construct(private array $events = [])
    {

    }

    public function add(RecordedEvent $event): void
    {
        if (isset($this->events[(string) $event->id()])) {
            return ;
        }

        $this->events[(string) $event->id()] = $event;
    }

    public function remove(RecordedEvent $event): void
    {
        if (!isset($this->events[(string) $event->id()])) {
            return ;
        }

        unset($this->events[(string) $event->id()]);
    }

    public function find(RecordedEventId $eventId): ?RecordedEvent
    {
        return (isset($this->events[(string) $eventId]))
            ? $this->events[(string) $eventId]
            : null
        ;
    }

    public function findByFilter(RecordedEventFilter $filter): array
    {
        $results = [];

        if (count($this->events) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->events as $event) {
            if (null !== $filter->userId && (null == $event->userId() || false == $filter->userId->equals($event->userId()))) {
                continue;
            }

            if (!empty($filter->eventName) && $filter->eventName !== $event->eventName()) {
                continue;
            }

            if (!empty($filter->context) && $filter->context !== $event->context()) {
                continue;
            }

            if (!empty($filter->aggregateRootId) && $filter->aggregateRootId !== $event->aggregateRootId()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $event;
        }

        return $results;
    }

    public function totalResultsByFilter(RecordedEventFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
