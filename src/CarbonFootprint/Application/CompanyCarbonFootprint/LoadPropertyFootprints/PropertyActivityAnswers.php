<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\Emissions;

final readonly class PropertyActivityAnswers
{
    /** @param list<SectionAnswer> $sectionAnswers */
    public function __construct(
        public array $sectionAnswers,
        public Emissions $emissions,
        public int $count,
    ) {}
}
