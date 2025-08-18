<?php

namespace App\Waste\Application\DailyWaste\Statistics;

class ByDate
{
    public function __construct(
        public \DateTimeImmutable $date,
        public float $load,
        public float $percentage,
    )
    {
        
    }
}