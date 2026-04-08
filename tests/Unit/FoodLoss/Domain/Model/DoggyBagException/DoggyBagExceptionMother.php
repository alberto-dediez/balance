<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\Security\Domain\Model\Account\AccountId;

class DoggyBagExceptionMother
{
    public static function registeredDoggyBagException(): DoggyBagException
    {
        return self::from(
            description: 'description',
            criticalPointId: (string) CriticalPointId::generate(),
            accountId: 'account-id',
            id: (string) DoggyBagExceptionId::generate(),
        );
    }

    public static function from(
        string $description,
        string $criticalPointId,
        string $accountId,
        string $id,
    ): DoggyBagException {
        $doggyBagException = new DoggyBagException(
            id: new DoggyBagExceptionId($id),
        );
        $doggyBagException->register(
            description: $description,
            criticalPointId: new CriticalPointId($criticalPointId),
            accountId: new AccountId($accountId),
        );

        return $doggyBagException;
    }

    public static function random(): DoggyBagException
    {
        return self::from(
            description: 'description-' . rand(100, 999),
            criticalPointId: (string) CriticalPointId::generate(),
            accountId: 'account-id',
            id: (string) DoggyBagExceptionId::generate(),
        );
    }

    public static function withCriticalPointId(CriticalPointId $criticalPointId): DoggyBagException
    {
        return self::from(
            description: 'description-' . rand(100, 999),
            criticalPointId: (string) $criticalPointId,
            accountId: 'account-id',
            id: (string) DoggyBagExceptionId::generate(),
        );
    }
}
