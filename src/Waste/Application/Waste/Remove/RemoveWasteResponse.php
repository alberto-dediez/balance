<?php

namespace App\Waste\Application\Waste\Remove;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveWasteResponse implements CommandResponse
{
    public function __construct(public WasteId $wasteId)
    {
        
    }
}