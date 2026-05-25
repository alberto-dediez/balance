<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Register;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\FoodType;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class RegisterSurplusRequest implements Command
{
    public function __construct(
        public \DateTimeImmutable $date = new \DateTimeImmutable('NOW'),
        public ?ServiceCategoryId $serviceCategoryId = null,
        public ?CriticalPointId $criticalPointId = null,
        public ?SurplusCauseId $surplusCauseId = null,
        public FoodType $foodType = FoodType::Processed,
        public float $quantity = 0.0,
        public DeliveryLogUnit $unit = DeliveryLogUnit::Kg,
        public ?AccountId $accountId = null,
        public ?UserId $registeredBy = null,
    ) {}
}
