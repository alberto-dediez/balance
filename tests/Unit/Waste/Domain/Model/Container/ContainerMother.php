<?php

namespace App\Tests\Unit\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;

class ContainerMother
{
    public static function registeredContainer()
    {   
        return self::from(
            containerId: ContainerId::generate(),
            wasteId: WasteId::generate(),
            propertyId: PropertyId::generate()
        );
    }

    public static function fromId(string $id)
    {
        return self::from(
            containerId: new ContainerId($id),
            wasteId: WasteId::generate(),
            propertyId: PropertyId::generate()
        );
    }

    public static function fromPropertyAndWaste(string $propertyId, string $wasteId)
    {
        return self::from(
            containerId: ContainerId::generate(),
            wasteId: new WasteId($wasteId),
            propertyId: new PropertyId($propertyId)
        );

        return $container;
    }

    public static function fromPropertyAndWasteAndId(string $propertyId, string $wasteId, string $containerId)
    {
        return self::from(
            containerId: new ContainerId($containerId),
            wasteId: new WasteId($wasteId),
            propertyId: new PropertyId($propertyId)
        );

        return $container;
    }

    public static function from(
        ContainerId $containerId, 
        WasteId $wasteId, 
        PropertyId $propertyId, 
        Purpose $purpose = Purpose::Waste, 
        MeasurementMethod $measurementMethod = MeasurementMethod::Volume
    ) {
        $container = new Container(
            id: $containerId
        );

        $container
            ->register(
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                measurementMethod: $measurementMethod
            )
        ;

        return $container;
    }

    public static function random()
    {
        $purpose = Purpose::cases()[rand(0, count(Purpose::cases())-1)];
        $measurementMethod = MeasurementMethod::cases()[rand(0, count(MeasurementMethod::cases())-1)];

        return self::from(
            containerId: ContainerId::generate(),
            wasteId: WasteId::generate(),
            propertyId: PropertyId::generate(),
            purpose: $purpose,
            measurementMethod: $measurementMethod
        );
    }
}