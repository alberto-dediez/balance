<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySettings\Update;

use App\Shared\Application\CommandBus\Command;

class UpdateMaterialitySettingsRequest implements Command
{
    public function __construct(
        public ?int $relevanceThresholdPercent = null,
        public bool $highMagnitudeForcesInclusion = true,
    ) {}
}
