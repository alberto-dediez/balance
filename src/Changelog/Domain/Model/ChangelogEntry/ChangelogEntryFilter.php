<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

use App\Shared\Domain\Model\Filter;

class ChangelogEntryFilter
{
    use Filter;

    public function __construct(
        public ?string $text = null,
        public ?ChangelogCategory $category = null,
        public bool $onlyPublished = true,
    ) {
        $this->limit = 10;
        $this->orderBy = 'date';
        $this->orderWay = 'DESC';
    }
}
