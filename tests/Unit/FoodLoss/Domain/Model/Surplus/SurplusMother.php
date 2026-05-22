<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\AnimalFeedAssessment;
use App\FoodLoss\Domain\Model\Surplus\CompostingAssessment;
use App\FoodLoss\Domain\Model\Surplus\EliminationAssessment;
use App\FoodLoss\Domain\Model\Surplus\FoodType;
use App\FoodLoss\Domain\Model\Surplus\HumanDonationAssessment;
use App\FoodLoss\Domain\Model\Surplus\PreventionAssessment;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\TransformationAssessment;
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
            preventionAssessment: new PreventionAssessment(),
            transformationAssessment: new TransformationAssessment(),
            humanDonationAssessment: new HumanDonationAssessment(),
            animalFeedAssessment: new AnimalFeedAssessment(),
            compostingAssessment: new CompostingAssessment(),
            eliminationAssessment: new EliminationAssessment(),
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
}
