<?php

namespace App\Waste\Application\Property\Remove;

use App\Waste\Domain\Model\Property\PropertyId;

class RemovePropertyRequest
{
    public function __construct(public PropertyId $propertyId)
    {

    }
}