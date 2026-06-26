<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\List;

class ListMobilitiesResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\Mobility\Mobility> $mobilities */
    public function __construct(public array $mobilities = [], public int $totalResults = 0) {}
}
