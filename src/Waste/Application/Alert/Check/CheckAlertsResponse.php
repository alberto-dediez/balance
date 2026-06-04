<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check;

use App\Shared\Application\CommandBus\CommandResponse;

class CheckAlertsResponse implements CommandResponse
{
    public function __construct(public readonly int $detectedAlerts = 0) {}
}
