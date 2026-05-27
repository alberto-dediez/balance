<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Update;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Application\CommandBus\Command;

class UpdateSurplusRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public \DateTimeImmutable $date,
        public ?ServiceCategoryId $serviceCategoryId,
        public ?CriticalPointId $criticalPointId,
        public ?SurplusCauseId $surplusCauseId,
        public FoodType $foodType,
        public float $quantity,
        public DeliveryLogUnit $unit,
    ) {}
}
