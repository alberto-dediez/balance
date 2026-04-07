<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\Security\Domain\Model\Account\AccountId;

class PackagingMother
{
    public static function registeredPackaging(): Packaging
    {
        return self::fromName('name');
    }

    public static function fromName(string $name): Packaging
    {
        return self::from(
            name: $name,
            id: (string) PackagingId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Packaging
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        string $accountId = 'account-id-test',
        PackagingType $packagingType = PackagingType::Reusable,
    ): Packaging {
        $packaging = new Packaging(
            id: new PackagingId($id),
        );
        $packaging->register(
            name: $name,
            packagingType: $packagingType,
            accountId: new AccountId($accountId),
        );

        return $packaging;
    }

    public static function random(): Packaging
    {
        return self::from(
            name: 'packaging-' . rand(100, 999),
            id: (string) PackagingId::generate(),
        );
    }
}
