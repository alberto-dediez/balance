<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertCheckQueue\Register;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;

class RegisterAlertCheckQueueResponse implements CommandResponse
{
    public function __construct(
        public readonly ?AlertCheckQueueId $alertCheckQueueId,
    ) {}
}
