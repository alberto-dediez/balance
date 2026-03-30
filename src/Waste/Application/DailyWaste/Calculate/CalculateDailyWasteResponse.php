<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Calculate;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;

class CalculateDailyWasteResponse implements CommandResponse
{
    public function __construct(
        public DailyWasteId $dailyWasteId,
        public float $load,
    ) {}
}
