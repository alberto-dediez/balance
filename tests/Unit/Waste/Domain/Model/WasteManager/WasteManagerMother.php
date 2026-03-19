<?php

namespace App\Tests\Unit\Waste\Domain\Model\WasteManager;

use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class WasteManagerMother
{
    public static function from(
        string $name, 
        bool $isPublic,
        AccountId $accountId,
        WasteManagerId $wasteManagerId
    ): WasteManager {
        $wasteManager = new WasteManager(
            id: $wasteManagerId
        );

        $wasteManager
            ->register(
                name: $name,
                isPublic: $isPublic,
                accountId: $accountId
            )
        ;

        return $wasteManager;
    }
    
    public static function fromAccount(AccountId $accountId)
    {
        return self::from(
            name: 'manager-' . random_int(99, 999),
            isPublic: random_int(0, 1) === 1,
            accountId: $accountId,
            wasteManagerId: WasteManagerId::generate()
        );
    }

    public static function random()
    {
        return self::from(
            name: 'manager-' . random_int(99, 999),
            isPublic: random_int(0, 1) === 1,
            accountId: AccountId::generate(),
            wasteManagerId: WasteManagerId::generate()
        );
    }

    public static function fromName(string $name)
    {
        return self::from(
            name: $name,
            isPublic: random_int(0, 1) === 1,
            accountId: AccountId::generate(),
            wasteManagerId: WasteManagerId::generate()
        );
    }

    public static function fromNameAndIdAndAccount(string $name, WasteManagerId $wasteManagerId, AccountId $accountId)
    {
        return self::from(
            name: $name,
            isPublic: random_int(0, 1) === 1,
            accountId: $accountId,
            wasteManagerId: $wasteManagerId
        );
    }
}