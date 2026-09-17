<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreConsumption;

use App\Property\Domain\Model\Property\PropertyId;

final readonly class BioscorePropertyConsumptions
{
    /**
     * @param list<BioscoreConsumption> $electricity
     * @param list<BioscoreConsumption> $combustibles
     * @param list<BioscoreConsumption> $refrigerantGases
     */
    public function __construct(
        public PropertyId $propertyId,
        public int $bioscoreId,
        public string $name,
        public int $year,
        public array $electricity = [],
        public array $combustibles = [],
        public array $refrigerantGases = [],
    ) {}
}
