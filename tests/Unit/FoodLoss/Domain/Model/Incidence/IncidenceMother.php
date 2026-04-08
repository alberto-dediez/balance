<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Incidence;

use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class IncidenceMother
{
    public static function registeredIncidence(): Incidence
    {
        return self::fromName('name');
    }

    public static function fromName(string $name): Incidence
    {
        return self::from(
            name: $name,
            id: (string) IncidenceId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Incidence
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function withPropertyId(PropertyId $propertyId): Incidence
    {
        return self::from(
            name: 'incidence-' . rand(100, 999),
            id: (string) IncidenceId::generate(),
            accountId: (string) AccountId::generate(),
            propertyId: $propertyId,
        );
    }

    public static function from(
        string $name,
        string $id,
        string $accountId = 'account-id-test',
        ?PropertyId $propertyId = null,
    ): Incidence {
        $incidence = new Incidence(
            id: new IncidenceId($id),
        );
        $incidence->register(
            name: $name,
            exceptionOccurredOn: new \DateTimeImmutable('2026-04-08 10:00:00'),
            incidenceType: IncidenceType::MachineryFailure,
            description: 'Description',
            effect: 'Effect',
            propertyId: $propertyId ?? PropertyId::generate(),
            accountId: new AccountId($accountId),
        );

        return $incidence;
    }

    public static function random(): Incidence
    {
        return self::from(
            name: 'incidence-' . rand(100, 999),
            id: (string) IncidenceId::generate(),
        );
    }
}
