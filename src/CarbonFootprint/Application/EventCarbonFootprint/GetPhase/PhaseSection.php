<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionDefinition;

final class PhaseSection
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
