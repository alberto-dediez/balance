<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\WasteManager;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class WasteManagerMother
{
    public static function from(
        string $name,
        bool $isPublic,
        AccountId $accountId,
        WasteManagerId $wasteManagerId,
    ): WasteManager {
        $wasteManager = new WasteManager(
            id: $wasteManagerId,
        );

        $wasteManager
            ->register(
                name: $name,
                isPublic: $isPublic,
                accountId: $accountId,
            )
        ;

        return $wasteManager;
    }

    public static function fromAccount(AccountId $accountId): WasteManager
    {
        return self::from(
            name: 'manager-' . random_int(99, 999),
            isPublic: 1 === random_int(0, 1),
            accountId: $accountId,
            wasteManagerId: WasteManagerId::generate(),
        );
    }

    public static function random(): WasteManager
    {
        return self::from(
            name: 'manager-' . random_int(99, 999),
            isPublic: 1 === random_int(0, 1),
            accountId: AccountId::generate(),
            wasteManagerId: WasteManagerId::generate(),
        );
    }

    public static function fromName(string $name): WasteManager
    {
        return self::from(
            name: $name,
            isPublic: 1 === random_int(0, 1),
            accountId: AccountId::generate(),
            wasteManagerId: WasteManagerId::generate(),
        );
    }

    public static function fromNameAndIdAndAccount(string $name, WasteManagerId $wasteManagerId, AccountId $accountId): WasteManager
    {
        return self::from(
            name: $name,
            isPublic: 1 === random_int(0, 1),
            accountId: $accountId,
            wasteManagerId: $wasteManagerId,
        );
    }
}
