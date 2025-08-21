<?php

namespace App\Waste\Application\DailyWaste\Calculate;

use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Shared\Application\CommandBus\CommandResponse;

class CalculateDailyWasteResponse implements CommandResponse
{
    public function __construct(
        public DailyWasteId $dailyWasteId, 
        public float $load
    ) {
        
    }
}