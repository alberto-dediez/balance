<?php

namespace App\Property\Application\Property\Remove;

use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemovePropertyResponse implements CommandResponse
{
    public function __construct(public PropertyId $propertyId)
    {
        
    }
}