<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Import;

use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportWasteResponse implements CommandResponse
{
    public function __construct(
        public WasteId $wasteId,
    ) {}
}
