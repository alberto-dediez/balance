<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

enum AnnouncementStatus: string
{
    case Draft = 'draft';

    case Scheduled = 'scheduled';

    case Published = 'published';
}
