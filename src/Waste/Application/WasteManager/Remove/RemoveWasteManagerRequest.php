<?php

namespace App\Waste\Application\WasteManager\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RemoveWasteManagerRequest implements Command
{
    public function __construct(public WasteManagerId $wasteManagerId)
    {

    }
}