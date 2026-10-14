<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Remove;

use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class RemovePropertyRequest implements Command
{
    public function __construct(public PropertyId $propertyId) {}
}
