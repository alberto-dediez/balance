<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Update;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Shared\Application\CommandBus\Command;

class UpdateAnnouncementRequest implements Command
{
    public function __construct(
        public AnnouncementId $announcementId,
        public ChangelogCategory $category = ChangelogCategory::Feature,
        public \DateTimeImmutable $date = new \DateTimeImmutable('today'),
        public ?string $version = null,
        public string $title = '',
        public string $summary = '',
        public string $body = '',
        public ?string $youtubeUrl = null,
        public ?string $manualLocalPath = null,
        public ?string $manualFileName = null,
        public bool $removeManual = false,
    ) {}
}
