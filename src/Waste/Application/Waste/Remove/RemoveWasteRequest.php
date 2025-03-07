<?php

namespace App\Waste\Application\Waste\Remove;

use App\Waste\Domain\Model\Waste\WasteId;

class RemoveWasteRequest
{
    public function __construct(public WasteId $wasteId)
    {

    }
}