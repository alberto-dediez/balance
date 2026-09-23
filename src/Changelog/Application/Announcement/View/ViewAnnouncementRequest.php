<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\View;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Shared\Application\CommandBus\Command;

class ViewAnnouncementRequest implements Command
{
    public function __construct(public AnnouncementId $announcementId) {}
}
