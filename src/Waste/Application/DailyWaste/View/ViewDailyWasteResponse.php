<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\View;

use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\Measurement\Measurement;

class ViewDailyWasteResponse
{
    public function __construct(
        public DailyWaste $dailyWaste,
        /** @var Measurement[] $measurements */
        public array $measurements = [],
    ) {}
}
