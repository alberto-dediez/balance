<?php

namespace App\Tests\Unit\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Tests\Unit\Waste\Infrastructure\QrCode\FakeQrCodeGenerator;

class ContainerMother
{
    public static function registeredContainer()
    {   
        return self::from(
            containerId: ContainerId::generate(),
            wasteId: WasteId::generate(),
            propertyId: PropertyId::generate(),
            accountId: AccountId::generate(),
            wasteManagerId: WasteManagerId::generate()
        );
    }

    public static function fromId(string $id)
    {
        return self::from(
            containerId: new ContainerId($id),
            wasteId: WasteId::generate(),
            propertyId: PropertyId::generate(),
            accountId: AccountId::generate(),
            wasteManagerId: WasteManagerId::generate()
        );
    }

    public static function fromPropertyAndWaste(string $propertyId, string $wasteId)
    {
        return self::from(
            accountId: AccountId::generate(),
            containerId: ContainerId::generate(),
            wasteId: new WasteId($wasteId),
            propertyId: new PropertyId($propertyId),
            wasteManagerId: WasteManagerId::generate()
        );

        return $container;
    }

    public static function fromPropertyAccountAndWaste(string $propertyId, string $accountId, string $wasteId)
    {
        return self::from(
            accountId: new AccountId($accountId),
            containerId: ContainerId::generate(),
            wasteId: new WasteId($wasteId),
            propertyId: new PropertyId($propertyId),
            wasteManagerId: WasteManagerId::generate()
        );

        return $container;
    }

    public static function fromPropertyAccountAndWasteAndId(string $propertyId, string $accountId, string $wasteId, string $containerId)
    {
        $container = self::from(
            accountId: new AccountId($accountId),
            containerId: new ContainerId($containerId),
            wasteId: new WasteId($wasteId),
            propertyId: new PropertyId($propertyId),
            wasteManagerId: WasteManagerId::generate()
        );

        return $container;
    }

    public static function fromPropertyAccountAndWasteAndIdAndManager(string $propertyId, string $accountId, string $wasteId, string $containerId, string $managerId)
    {
        $container = self::from(
            accountId: new AccountId($accountId),
            containerId: new ContainerId($containerId),
            wasteId: new WasteId($wasteId),
            propertyId: new PropertyId($propertyId),
            wasteManagerId: new WasteManagerId($managerId)
        );

        return $container;
    }

    public static function fromAccount(AccountId $accountId)
    {
        $purpose = Purpose::cases()[rand(0, count(Purpose::cases())-1)];
        $measurementMethod = MeasurementMethod::cases()[rand(0, count(MeasurementMethod::cases())-1)];

        return self::from(
            accountId: $accountId,
            containerId: ContainerId::generate(),
            wasteId: WasteId::generate(),
            propertyId: PropertyId::generate(),
            purpose: $purpose,
            measurementMethod: $measurementMethod,
            wasteManagerId: WasteManagerId::generate()
        );
    }

    public static function from(
        AccountId $accountId,
        ContainerId $containerId, 
        WasteId $wasteId, 
        PropertyId $propertyId, 
        WasteManagerId $wasteManagerId,
        Purpose $purpose = Purpose::Waste, 
        MeasurementMethod $measurementMethod = MeasurementMethod::Volume,
        string $volume = '10'
    ) {
        $container = new Container(
            id: $containerId
        );

        $qrCode = (new FakeQrCodeGenerator())->generate($containerId);

        $container
            ->register(
                accountId: $accountId,
                propertyId: $propertyId,
                wasteId: $wasteId,
                purpose: $purpose,
                measurementMethod: $measurementMethod,
                volume: $volume,
                wasteManagerId: $wasteManagerId,
                qrCode: $qrCode
            )
        ;

        return $container;
    }

    public static function random()
    {
        $purpose = Purpose::cases()[rand(0, count(Purpose::cases())-1)];
        $measurementMethod = MeasurementMethod::cases()[rand(0, count(MeasurementMethod::cases())-1)];

        return self::from(
            accountId: AccountId::generate(),
            containerId: ContainerId::generate(),
            wasteId: WasteId::generate(),
            propertyId: PropertyId::generate(),
            purpose: $purpose,
            measurementMethod: $measurementMethod,
            volume: rand(10, 100),
            wasteManagerId: WasteManagerId::generate()
        );
    }
}