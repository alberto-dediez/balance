<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySettings\Update;

use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMaterialitySettingsResponse implements CommandResponse
{
    public function __construct(
        public MaterialitySettingsId $materialitySettingsId,
        public int $relevanceThresholdPercent,
        public bool $highMagnitudeForcesInclusion,
    ) {}
}
