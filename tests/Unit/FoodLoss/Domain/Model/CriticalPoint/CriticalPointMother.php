<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\PriorityAssessment;
use App\FoodLoss\Domain\Model\CriticalPoint\SupplyChainStage;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class CriticalPointMother
{
    public static function registeredCriticalPoint(): CriticalPoint
    {
        return self::fromName('name');
    }

    public static function fromName(string $name): CriticalPoint
    {
        return self::from(
            name: $name,
            id: (string) CriticalPointId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): CriticalPoint
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromNameAndAccountAndProperty(string $name, AccountId $accountId, PropertyId $propertyId): CriticalPoint
    {
        return self::from(
            name: $name,
            id: (string) CriticalPointId::generate(),
            accountId: $accountId,
            propertyId: $propertyId,
        );
    }

    public static function from(
        string $name,
        string $id,
        ?AccountId $accountId = null,
        ?PropertyId $propertyId = null,
    ): CriticalPoint {
        $criticalPoint = new CriticalPoint(
            id: new CriticalPointId($id),
        );
        $criticalPoint->register(
            name: $name,
            propertyId: $propertyId ?? PropertyId::generate(),
            accountId: $accountId ?? AccountId::generate(),
            supplyChainStage: SupplyChainStage::Receiving,
            prevention: new PriorityAssessment(Priority::Prevention, false),
            humanDonation: new PriorityAssessment(Priority::HumanDonation, false),
            animalFeed: new PriorityAssessment(Priority::AnimalFeed, false),
            transformation: new PriorityAssessment(Priority::Transformation, false),
            composting: new PriorityAssessment(Priority::Composting, false),
        );

        return $criticalPoint;
    }

    public static function withServiceCategories(ServiceCategoryId ...$ids): CriticalPoint
    {
        $criticalPoint = new CriticalPoint(id: CriticalPointId::generate());
        $criticalPoint->register(
            name: 'critical-point-' . random_int(100, 999),
            propertyId: PropertyId::generate(),
            accountId: AccountId::generate(),
            supplyChainStage: SupplyChainStage::Receiving,
            prevention: new PriorityAssessment(Priority::Prevention, false),
            humanDonation: new PriorityAssessment(Priority::HumanDonation, false),
            animalFeed: new PriorityAssessment(Priority::AnimalFeed, false),
            transformation: new PriorityAssessment(Priority::Transformation, false),
            composting: new PriorityAssessment(Priority::Composting, false),
            serviceCategoryIds: array_values($ids),
        );

        return $criticalPoint;
    }

    public static function withImplementedAction(PropertyId $propertyId, CriticalPointId $criticalPointId, ActionId $actionId): CriticalPoint
    {
        $criticalPoint = new CriticalPoint(id: $criticalPointId);
        $criticalPoint->register(
            name: 'critical-point-' . random_int(100, 999),
            propertyId: $propertyId,
            accountId: AccountId::generate(),
            supplyChainStage: SupplyChainStage::Receiving,
            prevention: new PriorityAssessment(Priority::Prevention, true, actionIds: [$actionId]),
            humanDonation: new PriorityAssessment(Priority::HumanDonation, false),
            animalFeed: new PriorityAssessment(Priority::AnimalFeed, false),
            transformation: new PriorityAssessment(Priority::Transformation, false),
            composting: new PriorityAssessment(Priority::Composting, false),
        );

        return $criticalPoint;
    }

    public static function random(): CriticalPoint
    {
        return self::from(
            name: 'critical-point-' . random_int(100, 999),
            id: (string) CriticalPointId::generate(),
        );
    }
}
