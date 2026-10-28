<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;

final class ReportedEmissionsEmissionFactorSelector implements EmissionFactorSelector
{
    public const string SOURCE = 'reported';

    public function activityType(): ActivityType
    {
        return ActivityType::ReportedEmissions;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: '1',
            co2: '1',
            ch4: '0',
            n2o: '0',
            year: $year,
            emissionFactorGroupId: $groupId,
            unit: Unit::Kilogram,
            source: self::SOURCE,
        );
    }
}
