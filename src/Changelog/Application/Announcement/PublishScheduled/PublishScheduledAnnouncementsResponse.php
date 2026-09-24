<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\PublishScheduled;

use App\Shared\Application\CommandBus\CommandResponse;

class PublishScheduledAnnouncementsResponse implements CommandResponse
{
    public function __construct(
        public int $published,
        public int $notified,
    ) {}
}
