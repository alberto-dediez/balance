<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;

interface ContestWinnerPicker
{
    /**
     * @param list<ContestEntryId> $candidates
     *
     * @return list<ContestEntryId>
     */
    public function pick(array $candidates, int $count): array;
}
