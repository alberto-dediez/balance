<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\Account\AccountId;

class WasteActivityMother
{
    public static function registered(
        ?Quantity $containerCapacity = null,
        ?float $fillPercentage = null,
        ?AccountId $accountId = null,
    ): WasteActivity {
        return WasteActivity::register(
            id: ActivityId::generate(),
            accountId: $accountId ?? new AccountId('account-id'),
            wasteId: WasteId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 75.0),
            containerCapacity: $containerCapacity,
            fillPercentage: $fillPercentage,
        );
    }
}
