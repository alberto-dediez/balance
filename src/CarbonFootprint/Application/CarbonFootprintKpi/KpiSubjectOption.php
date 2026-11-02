<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

final readonly class KpiSubjectOption
{
    /** @param list<int> $completedYears */
    public function __construct(
        public string $id,
        public string $name,
        public array $completedYears,
    ) {}
}
