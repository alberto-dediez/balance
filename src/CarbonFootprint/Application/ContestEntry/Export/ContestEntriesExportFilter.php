<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Export;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;

class ContestEntriesExportFilter
{
    public function __construct(
        public ?ContestId $contestId = null,
    ) {}
}
