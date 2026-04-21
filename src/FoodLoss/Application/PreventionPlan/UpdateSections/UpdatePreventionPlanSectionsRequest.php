<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\UpdateSections;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\Command;

class UpdatePreventionPlanSectionsRequest implements Command
{
    /** @param SectionData[] $sections */
    public function __construct(
        public PreventionPlanId $preventionPlanId,
        public array $sections = [],
    ) {}
}
