<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

class ChangelogEntryNotFoundException extends \RuntimeException
{
    public function __construct(ChangelogEntrySlug $slug)
    {
        parent::__construct(sprintf('Changelog entry "%s" not found', (string) $slug));
    }
}
