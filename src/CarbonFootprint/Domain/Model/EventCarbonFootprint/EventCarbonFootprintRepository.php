<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\EventOperations\Domain\Model\Event\EventId;

interface EventCarbonFootprintRepository
{
    public function add(EventCarbonFootprint $eventCarbonFootprint): void;

    public function remove(EventCarbonFootprint $eventCarbonFootprint): void;

    public function find(EventCarbonFootprintId $eventCarbonFootprintId): ?EventCarbonFootprint;

    public function findByEventId(EventId $eventId): ?EventCarbonFootprint;

    public function findByActivityId(ActivityId $activityId): ?EventCarbonFootprint;

    /** @return array<EventCarbonFootprint> */
    public function findByFilter(EventCarbonFootprintFilter $filter): array;

    public function totalResultsByFilter(EventCarbonFootprintFilter $filter): int;
}
