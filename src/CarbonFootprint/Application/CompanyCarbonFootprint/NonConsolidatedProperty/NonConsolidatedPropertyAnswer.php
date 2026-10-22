<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty;

use App\Property\Domain\Model\Property\PropertyId;

final readonly class NonConsolidatedPropertyAnswer
{
    public function __construct(
        public PropertyId $propertyId,
        public ?string $manualCo2eq,
    ) {}
}
