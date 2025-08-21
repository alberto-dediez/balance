<?php

namespace App\Waste\Application\Waste\Remove;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\Command;

class RemoveWasteRequest implements Command
{
    public function __construct(public WasteId $wasteId)
    {

    }
}