<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Unpublish;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Shared\Application\CommandBus\CommandResponse;

class UnpublishAnnouncementResponse implements CommandResponse
{
    public function __construct(public AnnouncementId $announcementId, public bool $wasScheduled) {}
}
