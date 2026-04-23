<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

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
        string $companyId = 'company-id',
    ): PreventionPlan {
        $preventionPlan = new PreventionPlan(
            id: new PreventionPlanId($id),
        );
        $preventionPlan->register(
            name: $name,
            propertyIds: [],
            companyId: new CompanyId($companyId),
            accountId: new AccountId($accountId),
            registeredBy: new UserId('user-id'),
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

    public static function withConfiguredStatus(string $name, string $id, string $accountId): PreventionPlan
    {
        $plan = self::from(name: $name, id: $id, accountId: $accountId);
        $plan->markAsConfigured();

        return $plan;
    }

    public static function withGeneratedSections(string $name, string $id, string $accountId): PreventionPlan
    {
        $plan = self::from(name: $name, id: $id, accountId: $accountId);
        $plan->markAsConfigured();

        $plan->addSection('Introducción', 'Contenido de la sección de introducción.');
        $plan->addSection('Diagnóstico', 'Contenido de la sección de diagnóstico.');

        $plan->markAsGenerated();

        return $plan;
    }

    public static function fromWithProperty(string $name, string $id, string $accountId, string $propertyId): PreventionPlan
    {
        $preventionPlan = new PreventionPlan(id: new PreventionPlanId($id));
        $preventionPlan->register(
            name: $name,
            propertyIds: [new PropertyId($propertyId)],
            companyId: new CompanyId('company-id'),
            accountId: new AccountId($accountId),
            registeredBy: new UserId('user-id'),
        );

        return $preventionPlan;
    }

    /** @param PropertyId[] $propertyIds */
    public static function withPropertyIds(array $propertyIds): PreventionPlan
    {
        $preventionPlan = new PreventionPlan(id: PreventionPlanId::generate());
        $preventionPlan->register(
            name: 'prevention-plan-' . random_int(100, 999),
            propertyIds: $propertyIds,
            companyId: new CompanyId('company-id'),
            accountId: new AccountId('account-' . random_int(100, 999)),
            registeredBy: new UserId('user-id'),
        );

        return $preventionPlan;
    }
}
