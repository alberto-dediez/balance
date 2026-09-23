<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Publish;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Shared\Application\CommandBus\CommandResponse;

class PublishAnnouncementResponse implements CommandResponse
{
    public function __construct(
        public AnnouncementId $announcementId,
        public AnnouncementStatus $status,
        public bool $notified,
        public ?\DateTimeImmutable $scheduledAt = null,
    ) {}
}
