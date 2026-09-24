<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\View;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Shared\Application\CommandBus\CommandResponse;

class ViewAnnouncementResponse implements CommandResponse
{
    public function __construct(public Announcement $announcement) {}
}
