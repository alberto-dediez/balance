<?php

namespace App\Property\Application\Property\Remove;

use App\Property\Domain\Model\Property\PropertyId;

class RemovePropertyRequest
{
    public function __construct(public PropertyId $propertyId)
    {

    }
}