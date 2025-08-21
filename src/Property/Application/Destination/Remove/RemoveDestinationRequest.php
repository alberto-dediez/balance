<?php

namespace App\Property\Application\Destination\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Destination\DestinationId;

class RemoveDestinationRequest implements Command
{
    public function __construct(public DestinationId $destinationId)
    {

    }
}