<?php

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class MeasurementMother
{
    public static function fromContainer(ContainerId $containerId)
    {
        return self::from(
            measurementId: MeasurementId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            wasteManagerId: WasteManagerId::generate(),
            containerId: $containerId,
            registeredBy: UserId::generate(),
            accountId: AccountId::generate()
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
        ?string $justification = null
    ) {
        $measurement = new Measurement(
            id: $measurementId
        );

        $container = new Container(
            id: $containerId,
            propertyId: $propertyId,
            wasteId: $wasteId,
            purpose: $purpose,
            measurementMethod: $measurementMethod,
            wasteManagerId: $wasteManagerId
        );

        $measurement
            ->register(
                container: $container,
                quantity: $quantity,
                load: $load,
                registeredBy: $registeredBy,
                justification: $justification,
                accountId: $accountId
            )
        ;

        return $measurement;
    }

    public static function random()
    {
        $purpose = Purpose::cases()[rand(0, count(Purpose::cases())-1)];
        $measurementMethod = MeasurementMethod::cases()[rand(0, count(MeasurementMethod::cases())-1)];

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
            quantity: rand(1, 9),
            load: rand(0, 100) / 100,
            justification: null
        );
    }

    public static function fromAccount(AccountId $accountId)
    {
        $purpose = Purpose::cases()[rand(0, count(Purpose::cases())-1)];
        $measurementMethod = MeasurementMethod::cases()[rand(0, count(MeasurementMethod::cases())-1)];

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
            quantity: rand(1, 9),
            load: rand(0, 100) / 100,
            justification: null
        );
    }
}