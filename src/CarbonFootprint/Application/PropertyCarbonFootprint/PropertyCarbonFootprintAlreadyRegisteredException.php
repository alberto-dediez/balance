<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint;

use App\Property\Domain\Model\Property\PropertyId;

class PropertyCarbonFootprintAlreadyRegisteredException extends \InvalidArgumentException
{
    public function __construct(PropertyId $propertyId, int $year)
    {
        parent::__construct(sprintf('A carbon footprint already exists for property "%s" and year "%d".', $propertyId, $year));
    }
}
