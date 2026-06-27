<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Remove;

use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\Command;

class RemoveWasteRequest implements Command
{
    public function __construct(public WasteId $wasteId) {}
}
