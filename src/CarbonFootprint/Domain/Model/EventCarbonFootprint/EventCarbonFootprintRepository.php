<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\EventOperations\Domain\Model\Event\EventId;

interface EventCarbonFootprintRepository
{
    public function add(EventCarbonFootprint $eventCarbonFootprint): void;

    public function remove(EventCarbonFootprint $eventCarbonFootprint): void;

    public function find(EventCarbonFootprintId $eventCarbonFootprintId): ?EventCarbonFootprint;

    public function findByEventId(EventId $eventId): ?EventCarbonFootprint;

    /** @return array<EventCarbonFootprint> */
    public function findByFilter(EventCarbonFootprintFilter $filter): array;

    public function totalResultsByFilter(EventCarbonFootprintFilter $filter): int;
}
