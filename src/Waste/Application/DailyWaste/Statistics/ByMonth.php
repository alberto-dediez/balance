<?php

namespace App\Waste\Application\DailyWaste\Statistics;

class ByMonth
{
    public function __construct(
        public \DateTimeImmutable $month,
        public float $load,
        public float $percentage,
    )
    {

    }
}
