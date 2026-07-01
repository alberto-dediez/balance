<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class EmissionFactorGroupId implements AggregateRootId
{
    use AggregateRootIdTrait;

    /**
     * Well-known id of the "default" EmissionFactorGroup (code `default`, AR6 GWP values), seeded by the
     * FASE 12 migration. Used as a temporary compatibility fallback by callers that do not yet resolve the
     * group explicitly (imports, event `rebuildFromPayload`) until FASE 3/6/9 wire the real group resolution.
     */
    private const string DEFAULT_ID = '00000000-0000-0000-0000-000000000001';

    public static function default(): self
    {
        return new self(self::DEFAULT_ID);
    }
}
