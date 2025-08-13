<?php

namespace App\Tests\Unit\Waste\Domain\Model\DailyWaste;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class DailyWasteMother
{
    public static function from(
        DailyWasteId $dailyWasteId, 
        PropertyId $propertyId,
        WasteId $wasteId, 
        WasteManagerId $wasteManagerId,
        Purpose $purpose = Purpose::Waste, 
        ?AccountId $accountId = null,
        ?\DateTimeImmutable $day = null,
        float $load = 0
    ) {
        $day = null === $day ? new \DateTimeImmutable("NOW") : $day;
        $accountId = null === $accountId ? AccountId::generate() : $accountId;

        $dailyWaste = new DailyWaste(
            id: $dailyWasteId
        );

        $dailyWaste
            ->register(
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
                accountId: $accountId,
                day: $day,
                load: $load
            )
        ;

        return $dailyWaste;
    }

    public static function random()
    {
        $purpose = Purpose::cases()[rand(0, count(Purpose::cases())-1)];
        
        return self::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            accountId: AccountId::generate(),
            purpose: $purpose,
            load: rand(0, 100) / 100,
            day: new \DateTimeImmutable(sprintf("NOW -%s DAYS", rand(0, 500)))
        );
    }

    public static function fromAccount(AccountId $accountId)
    {
        $purpose = Purpose::cases()[rand(0, count(Purpose::cases())-1)];
        
        return self::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            accountId: $accountId,
            purpose: $purpose,
            load: rand(0, 100) / 100,
            day: new \DateTimeImmutable(sprintf("NOW -%s DAYS", rand(0, 500)))
        );
    }
}