<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\RecordedEvent;

interface RecordedEventRepository
{
    public function add(RecordedEvent $event): void;

    public function remove(RecordedEvent $event): void;

    public function find(RecordedEventId $eventId): ?RecordedEvent;

    /** @return list<RecordedEvent> */
    public function findByFilter(RecordedEventFilter $filter): array;

    public function totalResultsByFilter(RecordedEventFilter $filter): int;
}
