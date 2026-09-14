<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\View;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;

class ViewChangelogEntryRequest
{
    public function __construct(public readonly ChangelogEntrySlug $slug) {}
}
