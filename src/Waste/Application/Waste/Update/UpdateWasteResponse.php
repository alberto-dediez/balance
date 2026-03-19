<?php

declare(strict_types=1);

namespace App\Waste\Application\Waste\Update;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Waste\WasteId;

class UpdateWasteResponse implements CommandResponse
{
    public function __construct(
        public WasteId $wasteId,
        public string $name,
        public bool $isDangerous,
        public string $density,
        public string $color,
    ) {}
}
