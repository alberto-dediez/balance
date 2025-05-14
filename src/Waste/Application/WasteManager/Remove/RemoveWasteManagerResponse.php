<?php

namespace App\Waste\Application\WasteManager\Remove;

use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RemoveWasteManagerResponse
{
    public function __construct(public WasteManagerId $wasteManagerId)
    {
        
    }
}