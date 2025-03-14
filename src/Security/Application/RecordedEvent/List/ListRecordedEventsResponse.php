<?php

namespace App\Security\Application\RecordedEvent\List;

class ListRecordedEventsResponse
{
    public function __construct(public array $events = [], public int $totalResults = 0)
    {
        
    }
}