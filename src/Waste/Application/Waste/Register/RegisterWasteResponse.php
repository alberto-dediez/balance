<?php

namespace App\Waste\Application\Waste\Register;

use App\Waste\Domain\Model\Waste\WasteId;

class RegisterWasteResponse
{
    public function __construct(public WasteId $wasteId, public string $name)
    {
        
    }
}