<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\Property\Domain\Model\Property\PropertyId;

final readonly class SkippedPropertyFootprint
{
    public function __construct(
        public PropertyId $propertyId,
        public string $propertyName,
        public PropertyFootprintSkipReason $reason,
    ) {}
}
