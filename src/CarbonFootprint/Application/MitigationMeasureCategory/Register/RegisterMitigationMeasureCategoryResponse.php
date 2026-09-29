<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\Register;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterMitigationMeasureCategoryResponse implements CommandResponse
{
    public function __construct(
        public MitigationMeasureCategoryId $mitigationMeasureCategoryId,
        public string $name,
    ) {}
}
