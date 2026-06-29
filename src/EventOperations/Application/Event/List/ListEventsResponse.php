<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\List;

class ListEventsResponse
{
    /** @param array<\App\EventOperations\Domain\Model\Event\Event> $events */
    public function __construct(public array $events = [], public int $totalResults = 0) {}
}
