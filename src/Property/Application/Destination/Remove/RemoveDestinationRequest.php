<?php

namespace App\Property\Application\Destination\Remove;

use App\Property\Domain\Model\Destination\DestinationId;

class RemoveDestinationRequest
{
    public function __construct(public DestinationId $destinationId)
    {

    }
}