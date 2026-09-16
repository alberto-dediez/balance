<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;

final class StageSection
{
    /**
     * @param list<Activity> $activities
     * @param list<object>   $catalog
     * @param list<object>   $secondaryCatalog
     */
    public function __construct(
        public readonly SectionDefinition $definition,
        public readonly array $activities,
        public readonly array $catalog,
        public readonly array $secondaryCatalog = [],
    ) {}
}
