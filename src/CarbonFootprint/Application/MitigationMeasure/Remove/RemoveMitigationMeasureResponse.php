<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Remove;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveMitigationMeasureResponse implements CommandResponse
{
    public function __construct(public MitigationMeasureId $mitigationMeasureId) {}
}
