<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Security\Domain\Model\Account\AccountId;

class PropertyMother
{
    public static function registeredProperty()
    {
        return self::fromName('name');
    }

    public static function fromName(string $name)
    {
        return self::from(
            propertyId: PropertyId::generate(),
            name: $name,
        );
    }

    public static function fromNameAndInfo(string $name, PropertyInfo $info)
    {
        return self::from(
            propertyId: PropertyId::generate(),
            name: $name,
            info: $info,
        );
    }

    public static function fromNameAndExternalId(string $name, ExternalId $externalId)
    {
        return self::from(
            propertyId: PropertyId::generate(),
            name: $name,
            externalId: $externalId,
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            propertyId: new PropertyId($id),
            name: $name,
        );
    }

    public static function fromNameAndAccountAndId(string $name, string $accountId, string $id)
    {
        return self::from(
            propertyId: new PropertyId($id),
            name: $name,
            accountId: new AccountId($accountId),
        );
    }

    public static function from(PropertyId $propertyId, string $name = 'name', ?AccountId $accountId = null, ?PropertyInfo $info = null, ?ExternalId $externalId = null): Property
    {
        $property = new Property(
            id: $propertyId,
        );

        $property->register(
            name: $name,
            accountId: $accountId,
            info: $info,
            externalId: $externalId,
        );

        return $property;
    }

    public static function random()
    {
        return self::fromName('property-' . random_int(100, 999));
    }
}
