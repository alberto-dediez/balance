<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\ViewTechnicalManual;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;

class ViewTechnicalManualResponse
{
    public function __construct(
        public readonly ChangelogEntry $entry,
        public readonly string $markdown,
    ) {}
}
