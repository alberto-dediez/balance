<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\Elimination;

use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\Command;

class AssessEliminationRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public bool $canBeEliminated,
    ) {}
}
