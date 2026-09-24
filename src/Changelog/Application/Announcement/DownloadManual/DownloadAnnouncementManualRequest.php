<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\DownloadManual;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class DownloadAnnouncementManualRequest implements Command
{
    public function __construct(
        public AnnouncementId $announcementId,
        public ?Locale $locale = null,
    ) {}
}
