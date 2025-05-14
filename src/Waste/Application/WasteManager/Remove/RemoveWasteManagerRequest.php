<?php

namespace App\Waste\Application\WasteManager\Remove;

use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RemoveWasteManagerRequest
{
    public function __construct(public WasteManagerId $wasteManagerId)
    {

    }
}