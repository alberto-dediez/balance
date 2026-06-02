<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check;

class CheckAlertsResponse
{
    public function __construct(public readonly int $detectedAlerts = 0) {}
}
