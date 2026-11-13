<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\Property\Domain\Model\Property\PropertyId;

final readonly class PropertyEmissionsDifference
{
    public function __construct(
        public PropertyId $propertyId,
        public string $propertyName,
        public string $propertyFootprintCo2eq,
        public string $recalculatedCo2eq,
    ) {}
}
