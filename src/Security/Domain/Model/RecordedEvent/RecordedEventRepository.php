<?php

namespace App\Security\Domain\Model\RecordedEvent;

use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;

interface RecordedEventRepository
{
    public function add(RecordedEvent $event): void;

    public function remove(RecordedEvent $event): void;

    public function find(RecordedEventId $eventId): ?RecordedEvent;

    /** @return list<RecordedEvent> */
    public function findByFilter(RecordedEventFilter $filter): array;

    public function totalResultsByFilter(RecordedEventFilter $filter): int;
}
