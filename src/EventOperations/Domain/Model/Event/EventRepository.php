<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

interface EventRepository
{
    public function add(Event $event): void;

    public function remove(Event $event): void;

    public function find(EventId $eventId): ?Event;

    /** @return array<Event> */
    public function findByFilter(EventFilter $filter): array;

    public function totalResultsByFilter(EventFilter $filter): int;
}
