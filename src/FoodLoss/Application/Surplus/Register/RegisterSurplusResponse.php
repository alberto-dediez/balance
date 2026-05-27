<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Register;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterSurplusResponse implements CommandResponse
{
    public function __construct(
        public SurplusId $surplusId,
        public \DateTimeImmutable $date,
        public FoodType $foodType,
        public float $quantity,
        public DeliveryLogUnit $unit,
    ) {}
}
