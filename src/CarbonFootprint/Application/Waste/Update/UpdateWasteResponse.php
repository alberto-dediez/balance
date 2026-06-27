<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Update;

use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateWasteResponse implements CommandResponse
{
    public function __construct(
        public WasteId $wasteId,
        public string $name,
    ) {}
}
