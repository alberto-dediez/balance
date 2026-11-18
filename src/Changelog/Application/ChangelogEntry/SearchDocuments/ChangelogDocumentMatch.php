<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\SearchDocuments;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;

final readonly class ChangelogDocumentMatch
{
    public const string SOURCE_ENTRY = 'entry';
    public const string SOURCE_TECHNICAL_MANUAL = 'technical_manual';

    public function __construct(
        public ChangelogEntry $entry,
        public string $source,
        public string $excerpt,
    ) {}
}
