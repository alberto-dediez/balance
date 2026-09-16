<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\Property\Domain\Model\Property\Property;

final class PropertyStageSections
{
    /** @param list<StageSection> $sections */
    public function __construct(
        public readonly ?Property $property,
        public readonly array $sections,
    ) {}
}
