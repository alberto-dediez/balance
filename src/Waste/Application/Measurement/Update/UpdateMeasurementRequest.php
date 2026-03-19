<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement\Update;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Measurement\MeasurementId;

class UpdateMeasurementRequest implements Command
{
    public function __construct(
        public MeasurementId $measurementId,
        public int $quantity,
        public float $load,
        public ?string $justification,
        public ?string $reference = null,
    ) {}
}
