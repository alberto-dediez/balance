<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\DailyWaste;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\Waste\WasteId;
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
        float $load = 0,
    ): DailyWaste {
        $day ??= new \DateTimeImmutable('NOW');
        $accountId ??= AccountId::generate();

        $dailyWaste = new DailyWaste(
            id: $dailyWasteId,
        );

        $dailyWaste
            ->register(
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                wasteManagerId: $wasteManagerId,
                day: $day,
                load: $load,
                accountId: $accountId,
            )
        ;

        return $dailyWaste;
    }

    public static function random()
    {
        $purpose = Purpose::cases()[random_int(0, count(Purpose::cases()) - 1)];

        return self::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            purpose: $purpose,
            accountId: AccountId::generate(),
            day: new \DateTimeImmutable(sprintf('NOW -%s DAYS', random_int(0, 500))),
            load: random_int(0, 100) / 100,
        );
    }

    public static function fromAccount(AccountId $accountId)
    {
        $purpose = Purpose::cases()[random_int(0, count(Purpose::cases()) - 1)];

        return self::from(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            purpose: $purpose,
            accountId: $accountId,
            day: new \DateTimeImmutable(sprintf('NOW -%s DAYS', random_int(0, 500))),
            load: random_int(0, 100) / 100,
        );
    }

    public static function fromDayAndIdAndAccount(string $day, string $dailyWasteId, string $accountId)
    {
        $purpose = Purpose::cases()[random_int(0, count(Purpose::cases()) - 1)];

        return self::from(
            dailyWasteId: new DailyWasteId($dailyWasteId),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            purpose: $purpose,
            accountId: new AccountId($accountId),
            day: new \DateTimeImmutable($day),
            load: random_int(0, 100) / 100,
        );
    }
}
