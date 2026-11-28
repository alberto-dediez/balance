<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\List;

use App\CarbonFootprint\Domain\Model\Contest\Contest;

class ListContestsResponse
{
    /** @param list<Contest> $contests */
    public function __construct(public array $contests = [], public int $totalResults = 0) {}
}
