<?php

namespace App\Waste\Application\waste\Remove;

use App\Waste\Domain\Model\waste\wasteId;

class RemovewasteResponse
{
    public function __construct(public wasteId $wasteId)
    {
        
    }
}