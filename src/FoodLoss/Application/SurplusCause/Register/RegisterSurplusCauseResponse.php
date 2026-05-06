<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\Register;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterSurplusCauseResponse implements CommandResponse
{
    public function __construct(
        public SurplusCauseId $surplusCauseId,
        public string $name,
    ) {}
}
