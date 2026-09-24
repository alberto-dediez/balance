<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Register;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterAnnouncementResponse implements CommandResponse
{
    public function __construct(public AnnouncementId $announcementId, public string $title) {}
}
