<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\List;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;

class ListUseProfilesResponse
{
    /** @param array<UseProfile> $useProfiles */
    public function __construct(public array $useProfiles = [], public int $totalResults = 0) {}
}
