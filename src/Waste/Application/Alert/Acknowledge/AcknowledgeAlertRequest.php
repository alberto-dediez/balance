<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Acknowledge;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Alert\AlertId;

class AcknowledgeAlertRequest implements Command
{
    public function __construct(public AlertId $alertId) {}
}
