<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Statistics;

class ByMonth
{
    public function __construct(
        public \DateTimeImmutable $month,
        public float $load,
        public float $percentage,
    ) {}
}
