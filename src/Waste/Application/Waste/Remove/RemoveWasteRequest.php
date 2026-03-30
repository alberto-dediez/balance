<?php

declare(strict_types=1);

namespace App\Waste\Application\Waste\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Waste\WasteId;

class RemoveWasteRequest implements Command
{
    public function __construct(public WasteId $wasteId) {}
}
