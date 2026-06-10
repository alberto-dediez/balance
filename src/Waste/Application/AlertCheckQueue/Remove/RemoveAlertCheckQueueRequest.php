<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertCheckQueue\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;

class RemoveAlertCheckQueueRequest implements Command
{
    public function __construct(
        public readonly AlertCheckQueueId $alertCheckQueueId,
    ) {}
}
