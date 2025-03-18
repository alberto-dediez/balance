<?php

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Container\MeasurementMethod;

class MeasurementMother
{
    public static function fromContainer(ContainerId $containerId)
    {
        return self::from(
            measurementId: MeasurementId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            containerId: $containerId,
            registeredBy: UserId::generate(),
        );
    }

    public static function from(
        MeasurementId $measurementId, 
        PropertyId $propertyId,
        WasteId $wasteId, 
        ContainerId $containerId, 
        UserId $registeredBy,
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
            measurementMethod: $measurementMethod
        );

        $measurement
            ->register(
                container: $container,
                quantity: $quantity,
                load: $load,
                registeredBy: $registeredBy,
                justification: $justification
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
            containerId: ContainerId::generate(),
            registeredBy: UserId::generate(),
            purpose: $purpose,
            measurementMethod: $measurementMethod,
            quantity: rand(1, 9),
            load: rand(0, 100) / 100,
            justification: null
        );
    }
}