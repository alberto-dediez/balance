<?php

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Waste\Domain\Model\Container\Purpose;

class ByPurpose
{
    public function __construct(
        public Purpose $purpose,
        public float $load,
        public float $percentage,
    )
    {
        
    }
}