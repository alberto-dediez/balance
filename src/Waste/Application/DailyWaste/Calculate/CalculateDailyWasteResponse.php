<?php

namespace App\Waste\Application\DailyWaste\Calculate;

use App\Waste\Domain\Model\DailyWaste\DailyWasteId;

class CalculateDailyWasteResponse
{
    public function __construct(
        public DailyWasteId $dailyWasteId, 
        public float $load
    ) {
        
    }
}