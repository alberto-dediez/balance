<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Notify;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Shared\Application\CommandBus\Command;

class NotifyAnnouncementRequest implements Command
{
    public function __construct(public AnnouncementId $announcementId) {}
}
