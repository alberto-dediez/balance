<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\DownloadManual;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;

class DownloadManualRequest
{
    public function __construct(public readonly ChangelogEntrySlug $slug) {}
}
