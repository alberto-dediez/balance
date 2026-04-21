<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\GenerateSections;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\Command;

final class GenerateSectionsRequest implements Command
{
    public function __construct(
        public readonly PreventionPlanId $preventionPlanId,
    ) {}
}
