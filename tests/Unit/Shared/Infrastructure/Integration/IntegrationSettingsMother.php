<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Infrastructure\Integration\IntegrationSettings;

class IntegrationSettingsMother
{
    public static function enabled(string $platform = 'biohydra', int $maxHops = 5): IntegrationSettings
    {
        return new IntegrationSettings(true, $platform, $maxHops);
    }

    public static function disabled(): IntegrationSettings
    {
        return new IntegrationSettings(false, 'biohydra', 5);
    }
}
