<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySettings;

use App\Materiality\Domain\Model\MaterialitySettings\MaterialityDecisionRule;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;

class MaterialitySettingsMother
{
    public static function default(): MaterialitySettings
    {
        return self::withThreshold(MaterialityDecisionRule::DEFAULT_THRESHOLD_PERCENT, true);
    }

    public static function withThreshold(int $thresholdPercent, bool $highMagnitudeForcesInclusion = true): MaterialitySettings
    {
        $materialitySettings = new MaterialitySettings(MaterialitySettingsId::generate());
        $materialitySettings->register($thresholdPercent, $highMagnitudeForcesInclusion);

        return $materialitySettings;
    }
}
