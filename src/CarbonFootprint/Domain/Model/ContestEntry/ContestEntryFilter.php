<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Shared\Domain\Model\Filter;

class ContestEntryFilter
{
    use Filter;

    public function __construct(
        public ContestId $contestId,
        public ?ContestEntryStatus $status = null,
        public ?bool $winner = null,
        public ?string $text = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
        $this->orderBy = 'entryNumber';
    }
}
