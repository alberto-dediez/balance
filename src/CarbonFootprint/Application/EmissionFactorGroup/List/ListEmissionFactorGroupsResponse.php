<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\List;

class ListEmissionFactorGroupsResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup> $emissionFactorGroups */
    public function __construct(public array $emissionFactorGroups = [], public int $totalResults = 0) {}
}
