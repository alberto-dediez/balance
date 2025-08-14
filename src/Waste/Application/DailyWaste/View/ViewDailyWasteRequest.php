<?php

namespace App\Waste\Application\DailyWaste\View;

use App\Waste\Domain\Model\DailyWaste\DailyWasteId;

class ViewDailyWasteRequest
{
    public function __construct(
        public DailyWasteId $dailyWasteId
    )
    {
        
    }
    
}