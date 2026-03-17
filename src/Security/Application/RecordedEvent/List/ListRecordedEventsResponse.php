<?php

namespace App\Security\Application\RecordedEvent\List;

use App\Security\Domain\Model\RecordedEvent\RecordedEvent;

class ListRecordedEventsResponse
{
    /**
     * @param RecordedEvent[] $events
     */
    public function __construct(public array $events = [], public int $totalResults = 0)
    {

    }
}
