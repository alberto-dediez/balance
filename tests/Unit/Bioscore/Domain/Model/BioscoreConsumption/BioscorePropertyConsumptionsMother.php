<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption;

use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumption;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscorePropertyConsumptions;
use App\Property\Domain\Model\Property\PropertyId;

class BioscorePropertyConsumptionsMother
{
    /**
     * @param list<BioscoreConsumption> $electricity
     * @param list<BioscoreConsumption> $combustibles
     * @param list<BioscoreConsumption> $refrigerantGases
     */
    public static function forProperty(
        string $propertyId,
        int $year = 2025,
        array $electricity = [],
        array $combustibles = [],
        array $refrigerantGases = [],
        int $bioscoreId = 1,
        string $name = 'Hotel',
    ): BioscorePropertyConsumptions {
        return new BioscorePropertyConsumptions(
            propertyId: new PropertyId($propertyId),
            bioscoreId: $bioscoreId,
            name: $name,
            year: $year,
            electricity: $electricity,
            combustibles: $combustibles,
            refrigerantGases: $refrigerantGases,
        );
    }
}
