<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Register;

use App\CarbonFootprint\Application\MitigationMeasure\Update\MitigationMeasureEffectData;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Application\CommandBus\Command;

class RegisterMitigationMeasureRequest implements Command
{
    /** @param array<int, MitigationMeasureEffectData> $effects */
    public function __construct(
        public string $name = '',
        public string $description = '',
        public ?MitigationMeasureCategoryId $categoryId = null,
        public array $effects = [],
    ) {}
}
