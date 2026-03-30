<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Waste\Domain\Model\WasteManager\WasteManager;

class ByWasteManager
{
    public function __construct(
        public WasteManager $wasteManager,
        public float $load,
        public float $percentage,
    ) {}
}
