<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\Elimination;

use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\CommandResponse;

class AssessEliminationResponse implements CommandResponse
{
    public function __construct(
        public readonly SurplusId $surplusId,
    ) {}
}
