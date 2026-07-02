<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Step;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;

interface StepViewBuilder
{
    /** @return array<string, mixed> */
    public function build(EventCarbonFootprint $eventCarbonFootprint): array;
}
