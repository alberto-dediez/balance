<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Shared\Domain\Model\Filter;

class AnnouncementFilter
{
    use Filter;

    public function __construct(
        public ?string $text = null,
        public ?ChangelogCategory $category = null,
        public ?AnnouncementStatus $status = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
        $this->orderBy = 'date';
        $this->orderWay = 'DESC';
    }
}
