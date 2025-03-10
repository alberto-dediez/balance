<?php

namespace App\Waste\Application\Property\Remove;

use App\Waste\Domain\Model\Property\PropertyId;

class RemovePropertyResponse
{
    public function __construct(public PropertyId $propertyId)
    {
        
    }
}