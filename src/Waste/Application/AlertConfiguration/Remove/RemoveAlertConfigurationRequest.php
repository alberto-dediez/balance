<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;

class RemoveAlertConfigurationRequest implements Command
{
    public function __construct(public AlertConfigurationId $alertConfigurationId) {}
}
