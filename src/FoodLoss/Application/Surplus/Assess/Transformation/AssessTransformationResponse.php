<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\Transformation;

use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\CommandResponse;

class AssessTransformationResponse implements CommandResponse
{
    public function __construct(
        public readonly SurplusId $surplusId,
    ) {}
}
