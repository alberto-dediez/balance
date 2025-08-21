<?php

namespace App\Waste\Application\Waste\Update;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateWasteResponse implements CommandResponse
{
    public function __construct(
        public WasteId $wasteId, 
        public string $name,
        public bool $isDangerous,
        public string $density,
        public string $color,
        
    ) {
        
    }
}