<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Update;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMitigationMeasureResponse implements CommandResponse
{
    public function __construct(
        public MitigationMeasureId $mitigationMeasureId,
        public string $name,
    ) {}
}
