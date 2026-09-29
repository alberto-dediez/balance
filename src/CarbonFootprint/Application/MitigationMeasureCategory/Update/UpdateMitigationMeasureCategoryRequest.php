<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\Update;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Application\CommandBus\Command;

class UpdateMitigationMeasureCategoryRequest implements Command
{
    public function __construct(
        public MitigationMeasureCategoryId $mitigationMeasureCategoryId,
        public string $name,
    ) {}
}
