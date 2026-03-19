<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement\Remove;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Measurement\MeasurementId;

class RemoveMeasurementResponse implements CommandResponse
{
    public function __construct(public MeasurementId $measurementId) {}
}
