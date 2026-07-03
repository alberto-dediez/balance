<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;

final class SectionDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly Source $source,
        public readonly ActivityType $activityType,
    ) {}
}
