<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;

final readonly class ProposedActionSuggestion
{
    public function __construct(
        public ActionId $actionId,
        public CriticalPointId $criticalPointId,
    ) {}
}
