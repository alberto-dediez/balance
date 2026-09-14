<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\View;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;

class ViewChangelogEntryResponse
{
    public function __construct(public readonly ChangelogEntry $entry) {}
}
