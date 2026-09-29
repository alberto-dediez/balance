<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Update;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Application\CommandBus\Command;

class UpdateMitigationMeasureRequest implements Command
{
    /** @param array<int, MitigationMeasureEffectData> $effects */
    public function __construct(
        public MitigationMeasureId $mitigationMeasureId,
        public string $name,
        public string $description,
        public ?MitigationMeasureCategoryId $categoryId,
        public array $effects = [],
    ) {}
}
