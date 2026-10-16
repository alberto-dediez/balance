<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Remove;

use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\Shared\Application\CommandBus\Command;

class RemoveTransportRequest implements Command
{
    public function __construct(public TransportId $transportId) {}
}
