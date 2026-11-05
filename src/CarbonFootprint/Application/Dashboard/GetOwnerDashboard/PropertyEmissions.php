<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard;

use App\Property\Domain\Model\Property\Property;

final class PropertyEmissions
{
    public function __construct(
        public readonly Property $property,
        public readonly float $co2eq,
    ) {}
}
