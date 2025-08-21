<?php

namespace App\Property\Application\Destination\Remove;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Property\Domain\Model\Destination\DestinationId;

class RemoveDestinationResponse implements CommandResponse
{
    public function __construct(public DestinationId $destinationId)
    {
        
    }
}