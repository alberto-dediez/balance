<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Register;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Shared\Application\CommandBus\Command;

class RegisterAnnouncementRequest implements Command
{
    public function __construct(
        public ChangelogCategory $category = ChangelogCategory::Feature,
        public \DateTimeImmutable $date = new \DateTimeImmutable('today'),
        public ?string $version = null,
        public string $title = '',
        public string $summary = '',
        public string $body = '',
        public ?string $youtubeUrl = null,
        public ?string $manualLocalPath = null,
        public ?string $manualFileName = null,
    ) {}
}
