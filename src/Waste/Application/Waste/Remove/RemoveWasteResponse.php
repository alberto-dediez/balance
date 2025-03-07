<?php

namespace App\Waste\Application\Waste\Remove;

use App\Waste\Domain\Model\Waste\WasteId;

class RemoveWasteResponse
{
    public function __construct(public WasteId $wasteId)
    {
        
    }
}