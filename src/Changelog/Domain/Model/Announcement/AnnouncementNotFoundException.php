<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

final class AnnouncementNotFoundException extends \RuntimeException
{
    public function __construct(AnnouncementId $announcementId)
    {
        parent::__construct(sprintf('Announcement "%s" not found', (string) $announcementId));
    }
}
