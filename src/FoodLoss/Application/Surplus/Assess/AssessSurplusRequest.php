<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess;

use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\Command;

class AssessSurplusRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public SurplusDestination $destination,
    ) {}
}
