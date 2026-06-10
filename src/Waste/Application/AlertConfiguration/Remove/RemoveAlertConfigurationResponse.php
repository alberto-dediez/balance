<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Remove;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;

class RemoveAlertConfigurationResponse implements CommandResponse
{
    public function __construct(public AlertConfigurationId $alertConfigurationId) {}
}
