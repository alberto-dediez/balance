<?php

namespace App\Waste\Application\WasteManager\Remove;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RemoveWasteManagerResponse implements CommandResponse
{
    public function __construct(public WasteManagerId $wasteManagerId)
    {
        
    }
}