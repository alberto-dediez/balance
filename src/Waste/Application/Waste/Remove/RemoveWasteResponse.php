<?php

declare(strict_types=1);

namespace App\Waste\Application\Waste\Remove;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Waste\WasteId;

class RemoveWasteResponse implements CommandResponse
{
    public function __construct(public WasteId $wasteId) {}
}
