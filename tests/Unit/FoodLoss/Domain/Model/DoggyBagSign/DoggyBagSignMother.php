<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class DoggyBagSignMother
{
    public static function registeredDoggyBagSign(): DoggyBagSign
    {
        return self::fromName('Cartel principal');
    }

    public static function fromName(string $name): DoggyBagSign
    {
        return self::from(
            name: $name,
            id: (string) DoggyBagSignId::generate(),
            accountId: 'account-id',
        );
    }

    public static function fromNameAndId(string $name, string $id): DoggyBagSign
    {
        return self::from(
            name: $name,
            id: $id,
            accountId: 'account-test',
        );
    }

    public static function forAccount(string $accountId): DoggyBagSign
    {
        return self::from(
            name: 'Cartel-' . random_int(100, 999),
            id: (string) DoggyBagSignId::generate(),
            accountId: $accountId,
        );
    }

    public static function withPropertyId(PropertyId $propertyId): DoggyBagSign
    {
        return self::from(
            name: 'Cartel-' . random_int(100, 999),
            id: (string) DoggyBagSignId::generate(),
            accountId: (string) AccountId::generate(),
            propertyId: $propertyId,
        );
    }

    public static function from(
        string $name,
        string $id,
        string $accountId,
        ?PropertyId $propertyId = null,
        string $location = 'Puerta principal',
        string $path = 'doggy_bag_signs/account-id/test.pdf',
    ): DoggyBagSign {
        $doggyBagSign = new DoggyBagSign(
            id: new DoggyBagSignId($id),
        );
        $doggyBagSign->register(
            name: $name,
            location: $location,
            path: $path,
            propertyId: $propertyId,
            accountId: new AccountId($accountId),
        );

        return $doggyBagSign;
    }

    public static function random(): DoggyBagSign
    {
        return self::from(
            name: 'cartel-' . random_int(100, 999),
            id: (string) DoggyBagSignId::generate(),
            accountId: 'account-' . random_int(100, 999),
        );
    }
}
