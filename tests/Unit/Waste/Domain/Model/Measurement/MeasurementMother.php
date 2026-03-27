<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class MeasurementMother
{
    public static function fromContainer(ContainerId $containerId): Measurement
    {
        return self::from(
            measurementId: MeasurementId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            containerId: $containerId,
            registeredBy: UserId::generate(),
            accountId: AccountId::generate(),
        );
    }

    public static function fromDay(DailyWaste $dailyWaste): Measurement
    {
        $measurementMethod = MeasurementMethod::cases()[random_int(0, count(MeasurementMethod::cases()) - 1)];

        assert($dailyWaste->propertyId() instanceof PropertyId);
        assert($dailyWaste->wasteId() instanceof WasteId);
        assert($dailyWaste->wasteManagerId() instanceof WasteManagerId);
        assert($dailyWaste->accountId() instanceof AccountId);

        return self::from(
            measurementId: MeasurementId::generate(),
            propertyId: $dailyWaste->propertyId(),
            wasteId: $dailyWaste->wasteId(),
            wasteManagerId: $dailyWaste->wasteManagerId(),
            containerId: ContainerId::generate(),
            registeredBy: UserId::generate(),
            accountId: $dailyWaste->accountId(),
            purpose: $dailyWaste->purpose(),
            measurementMethod: $measurementMethod,
            quantity: random_int(1, 9),
            load: random_int(0, 100) / 100,
            justification: null,
            registeredAt: $dailyWaste->day(),
        );
    }

    public static function from(
        MeasurementId $measurementId,
        PropertyId $propertyId,
        WasteId $wasteId,
        WasteManagerId $wasteManagerId,
        ContainerId $containerId,
        UserId $registeredBy,
        AccountId $accountId,
        Purpose $purpose = Purpose::Waste,
        MeasurementMethod $measurementMethod = MeasurementMethod::Volume,
        int $quantity = 0,
        float $load = 0,
        ?string $justification = null,
        ?\DateTimeImmutable $registeredAt = null,
    ): Measurement {

        if ($registeredAt instanceof \DateTimeImmutable) {
            return new Measurement(
                id: $measurementId,
                containerId: $containerId,
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                measurementMethod: $measurementMethod,
                wasteManagerId: $wasteManagerId,
                quantity: $quantity,
                load: $load,
                justification: $justification,
                accountId: $accountId,
                registeredBy: $registeredBy,
                registeredAt: $registeredAt,
            );
        }

        $measurement = new Measurement(
            id: $measurementId,
        );

        $container = new Container(
            id: $containerId,
            propertyId: $propertyId,
            wasteId: $wasteId,
            purpose: $purpose,
            measurementMethod: $measurementMethod,
            wasteManagerId: $wasteManagerId,
        );

        $measurement
            ->register(
                container: $container,
                quantity: $quantity,
                load: $load,
                registeredBy: $registeredBy,
                accountId: $accountId,
                justification: $justification,
            )
        ;

        return $measurement;
    }

    public static function random(): Measurement
    {
        $purpose = Purpose::cases()[random_int(0, count(Purpose::cases()) - 1)];
        $measurementMethod = MeasurementMethod::cases()[random_int(0, count(MeasurementMethod::cases()) - 1)];

        return self::from(
            measurementId: MeasurementId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            containerId: ContainerId::generate(),
            registeredBy: UserId::generate(),
            accountId: AccountId::generate(),
            purpose: $purpose,
            measurementMethod: $measurementMethod,
            quantity: random_int(1, 9),
            load: random_int(0, 100) / 100,
        );
    }

    public static function fromAccount(AccountId $accountId): Measurement
    {
        $purpose = Purpose::cases()[random_int(0, count(Purpose::cases()) - 1)];
        $measurementMethod = MeasurementMethod::cases()[random_int(0, count(MeasurementMethod::cases()) - 1)];

        return self::from(
            measurementId: MeasurementId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            containerId: ContainerId::generate(),
            registeredBy: UserId::generate(),
            accountId: $accountId,
            purpose: $purpose,
            measurementMethod: $measurementMethod,
            quantity: random_int(1, 9),
            load: random_int(0, 100) / 100,
        );
    }
}
