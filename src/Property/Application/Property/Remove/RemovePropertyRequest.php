<?php

namespace App\Property\Application\Property\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Property\PropertyId;

class RemovePropertyRequest implements Command
{
    public function __construct(public PropertyId $propertyId)
    {

    }
}