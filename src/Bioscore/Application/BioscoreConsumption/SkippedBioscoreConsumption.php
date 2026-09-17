<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption;

use App\Property\Domain\Model\Property\PropertyId;

final readonly class SkippedBioscoreConsumption
{
    public function __construct(
        public int $bioscoreId,
        public string $name,
        public float $consumption,
        public string $unit,
        public BioscoreConsumptionSkipReason $reason,
        public ?PropertyId $propertyId = null,
    ) {}
}
