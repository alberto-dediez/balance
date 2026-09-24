<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\List;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Shared\Application\CommandBus\CommandResponse;

class ListAnnouncementsResponse implements CommandResponse
{
    /**
     * @param list<Announcement> $announcements
     */
    public function __construct(public array $announcements = [], public int $totalResults = 0) {}
}
