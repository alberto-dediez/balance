<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;

final class SectionAnswer
{
    /** @param list<ActivityAnswer> $answers */
    public function __construct(
        public readonly SectionDefinition $definition,
        public readonly array $answers,
    ) {}
}
