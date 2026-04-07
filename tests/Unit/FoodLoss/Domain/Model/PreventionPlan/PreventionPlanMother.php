<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class PreventionPlanMother
{
    public static function registeredPreventionPlan(): PreventionPlan
    {
        return self::fromName('Prevention Plan');
    }

    public static function fromName(string $name): PreventionPlan
    {
        return self::from(
            name: $name,
            id: (string) PreventionPlanId::generate(),
            accountId: 'account-id',
        );
    }

    public static function fromNameAndId(string $name, string $id): PreventionPlan
    {
        return self::from(
            name: $name,
            id: $id,
            accountId: 'account-id',
        );
    }

    public static function from(
        string $name,
        string $id,
        string $accountId = 'account-id',
    ): PreventionPlan {
        $preventionPlan = new PreventionPlan(
            id: new PreventionPlanId($id),
        );
        $preventionPlan->register(
            name: $name,
            propertyIds: [],
            accountId: new AccountId($accountId),
        );

        return $preventionPlan;
    }

    public static function forAccount(string $accountId): PreventionPlan
    {
        return self::from(
            name: 'prevention-plan-' . random_int(100, 999),
            id: (string) PreventionPlanId::generate(),
            accountId: $accountId,
        );
    }

    public static function random(): PreventionPlan
    {
        return self::from(
            name: 'prevention-plan-' . random_int(100, 999),
            id: (string) PreventionPlanId::generate(),
            accountId: 'account-' . random_int(100, 999),
        );
    }

    /** @param PropertyId[] $propertyIds */
    public static function withPropertyIds(array $propertyIds): PreventionPlan
    {
        $preventionPlan = new PreventionPlan(id: PreventionPlanId::generate());
        $preventionPlan->register(
            name: 'prevention-plan-' . random_int(100, 999),
            propertyIds: $propertyIds,
            accountId: new AccountId('account-' . random_int(100, 999)),
        );

        return $preventionPlan;
    }
}
