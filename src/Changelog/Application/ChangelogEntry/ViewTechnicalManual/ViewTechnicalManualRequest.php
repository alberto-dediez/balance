<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\ViewTechnicalManual;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;

class ViewTechnicalManualRequest
{
    public function __construct(public readonly ChangelogEntrySlug $slug) {}
}
