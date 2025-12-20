<?php

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;

class StatisticsDailyWasteRequest extends DailyWasteFilter
{
    public function filter(): DailyWasteFilter
    {
        $this->limit = 0;

        return $this;
    }
}
