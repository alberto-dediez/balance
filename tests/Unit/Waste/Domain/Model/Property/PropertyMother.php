<?php

namespace App\Tests\Unit\Waste\Domain\Model\Property;

use App\Waste\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\Account;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class PropertyMother
{
    public static function registeredProperty()
    {   
        return self::fromName('name');
    }

    public static function fromName($name)
    {
        return self::from(
            propertyId: PropertyId::generate(),
            name: $name
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            propertyId: new PropertyId($id),
            name: $name
        );
    }

    public static function fromNameAndAccountAndId(string $name, string $accountId, string $id)
    {
        return self::from(
            propertyId: new PropertyId($id),
            name: $name,
            accountId: new AccountId($accountId)
        );
    }

    public static function from(PropertyId $propertyId, string $name = 'name', ?AccountId $accountId = null)
    {
        $property = new Property(
            id: $propertyId
        );

        $property->register($name, $accountId);

        return $property;
    }

    public static function random()
    {
        return self::fromName('property-' . rand(100, 999));
    }
}