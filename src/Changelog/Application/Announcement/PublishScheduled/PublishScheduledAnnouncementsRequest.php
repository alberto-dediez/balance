<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\PublishScheduled;

use App\Shared\Application\CommandBus\TimedCommand;

class PublishScheduledAnnouncementsRequest implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
