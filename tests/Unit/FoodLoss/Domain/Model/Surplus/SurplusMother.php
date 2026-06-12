<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class SurplusMother
{
    public static function registeredSurplus(): Surplus
    {
        return self::from(
            id: (string) SurplusId::generate(),
            accountId: 'account-id-test',
        );
    }

    public static function from(
        string $id,
        string $accountId,
        FoodType $foodType = FoodType::Vegetables,
        float $quantity = 5.0,
        DeliveryLogUnit $unit = DeliveryLogUnit::Kg,
        ?\DateTimeImmutable $date = null,
    ): Surplus {
        $surplus = new Surplus(
            id: new SurplusId($id),
            accountId: new AccountId($accountId),
        );
        $surplus->register(
            date: $date ?? new \DateTimeImmutable('2026-05-21'),
            serviceCategoryId: new ServiceCategoryId('00000000-0000-0000-0000-000000000001'),
            criticalPointId: new CriticalPointId('00000000-0000-0000-0000-000000000001'),
            surplusCauseId: new SurplusCauseId('00000000-0000-0000-0000-000000000001'),
            foodType: $foodType,
            quantity: $quantity,
            unit: $unit,
            accountId: new AccountId($accountId),
            registeredBy: new UserId('user-id-test'),
        );

        return $surplus;
    }

    public static function fromIdAndAccount(string $id, string $accountId): Surplus
    {
        return self::from(id: $id, accountId: $accountId);
    }

    public static function random(): Surplus
    {
        return self::from(
            id: (string) SurplusId::generate(),
            accountId: (string) AccountId::generate(),
        );
    }

    public static function withAccountId(AccountId $accountId): Surplus
    {
        return self::from(
            id: (string) SurplusId::generate(),
            accountId: (string) $accountId,
        );
    }

    public static function withAccountIdAndDate(AccountId $accountId, \DateTimeImmutable $date): Surplus
    {
        return self::from(
            id: (string) SurplusId::generate(),
            accountId: (string) $accountId,
            date: $date,
        );
    }

    public static function withIncidenceId(string $id, string $accountId, IncidenceId $incidenceId): Surplus
    {
        $surplus = new Surplus(
            id: new SurplusId($id),
            accountId: new AccountId($accountId),
        );
        $surplus->register(
            date: new \DateTimeImmutable('2026-05-21'),
            serviceCategoryId: new ServiceCategoryId('00000000-0000-0000-0000-000000000001'),
            criticalPointId: new CriticalPointId('00000000-0000-0000-0000-000000000001'),
            surplusCauseId: new SurplusCauseId('00000000-0000-0000-0000-000000000001'),
            foodType: FoodType::Vegetables,
            quantity: 5.0,
            unit: DeliveryLogUnit::Kg,
            accountId: new AccountId($accountId),
            registeredBy: new UserId('user-id-test'),
            incidenceId: $incidenceId,
        );

        return $surplus;
    }

    public static function withAllAssessmentsAnsweredNo(AccountId $accountId): Surplus
    {
        $surplus = self::withAccountId($accountId);

        $surplus->assessPrevention(false, null);
        $surplus->assessTransformation(false, null);
        $surplus->assessHumanDonation(false, null);
        $surplus->assessAnimalFeed(false, null);
        $surplus->assessComposting(false, null);
        $surplus->assessElimination(false);

        return $surplus;
    }
}
