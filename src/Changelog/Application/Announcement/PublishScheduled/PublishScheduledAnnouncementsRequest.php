<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\PublishScheduled;

use App\Shared\Application\CommandBus\Command;

class PublishScheduledAnnouncementsRequest implements Command
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
