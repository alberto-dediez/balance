<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Acknowledge;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Alert\AlertId;

class AcknowledgeAlertResponse implements CommandResponse
{
    public function __construct(public AlertId $alertId) {}
}
