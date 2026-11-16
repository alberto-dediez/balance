<?php

declare(strict_types=1);

namespace App\Assistant\Application\Usage\View;

use App\Shared\Application\Period\DailyPeriod;

class ViewAssistantUsageRequest
{
    public function __construct(public DailyPeriod $period) {}
}
