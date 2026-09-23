<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Notify;

use App\Shared\Application\CommandBus\CommandResponse;

class NotifyAnnouncementResponse implements CommandResponse
{
    public function __construct(public int $recipientCount) {}
}
