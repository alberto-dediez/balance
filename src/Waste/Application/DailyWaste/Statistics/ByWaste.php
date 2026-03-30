<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Waste\Domain\Model\Waste\Waste;

class ByWaste
{
    public function __construct(
        public Waste $waste,
        public float $load,
        public float $percentage,
    ) {}
}
