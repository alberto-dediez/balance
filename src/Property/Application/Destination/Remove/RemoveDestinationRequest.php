<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Remove;

use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Application\CommandBus\Command;

class RemoveDestinationRequest implements Command
{
    public function __construct(public DestinationId $destinationId) {}
}
