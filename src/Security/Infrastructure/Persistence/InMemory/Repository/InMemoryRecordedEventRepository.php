<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryRecordedEventRepository implements RecordedEventRepository
{
    /**
     * @param RecordedEvent[] $events
     */
    public function __construct(private array $events = []) {}

    public function add(RecordedEvent $event): void
    {
        if (isset($this->events[(string) $event->id()])) {
            return;
        }

        $this->events[(string) $event->id()] = $event;
    }

    public function remove(RecordedEvent $event): void
    {
        if (!isset($this->events[(string) $event->id()])) {
            return;
        }

        unset($this->events[(string) $event->id()]);
    }

    public function find(RecordedEventId $eventId): ?RecordedEvent
    {
        return $this->events[(string) $eventId] ?? null
        ;
    }

    /**
     * @return list<RecordedEvent>
     */
    public function findByFilter(RecordedEventFilter $filter): array
    {
        $results = [];

        if (0 === count($this->events)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->events as $event) {
            if ($filter->userId instanceof UserId && (null == $event->userId() || false === $filter->userId->equals($event->userId()))) {
                continue;
            }

            if (!in_array($filter->eventName, [null, '', '0', $event->eventName()], true)) {
                continue;
            }

            if (!in_array($filter->context, [null, '', '0', $event->context()], true)) {
                continue;
            }

            if (!in_array($filter->aggregateRootId, [null, '', '0', $event->aggregateRootId()], true)) {
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

    public function totalResultsByFilter(RecordedEventFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
