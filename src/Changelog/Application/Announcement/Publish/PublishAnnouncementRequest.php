<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Publish;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Shared\Application\CommandBus\Command;

class PublishAnnouncementRequest implements Command
{
    public function __construct(
        public AnnouncementId $announcementId,
        public ?\DateTimeImmutable $publishAt = null,
        public bool $notifyUsers = false,
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
