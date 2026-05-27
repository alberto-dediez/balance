<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\WasteManager;

use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Security\Domain\Model\Account\AccountId;

class WasteManagerMother
{
    public static function registeredWasteManager(): WasteManager
    {
        return self::fromName('Gestor de residuos');
    }

    public static function fromName(string $name): WasteManager
    {
        return self::from(
            name: $name,
            id: (string) WasteManagerId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): WasteManager
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromAccount(string $accountId): WasteManager
    {
        return self::from(
            name: 'waste-manager-' . random_int(100, 999),
            id: (string) WasteManagerId::generate(),
            accountId: $accountId,
        );
    }

    public static function from(
        string $name,
        string $id,
        string $accountId = 'account-id-test',
        bool $animalFeedAuthorized = false,
        bool $compostAuthorized = false,
    ): WasteManager {
        $wasteManager = new WasteManager(
            id: new WasteManagerId($id),
        );
        $wasteManager->register(
            name: $name,
            animalFeedAuthorized: $animalFeedAuthorized,
            compostAuthorized: $compostAuthorized,
            accountId: new AccountId($accountId),
        );

        return $wasteManager;
    }

    public static function random(): WasteManager
    {
        return self::from(
            name: 'waste-manager-' . random_int(100, 999),
            id: (string) WasteManagerId::generate(),
        );
    }
}
