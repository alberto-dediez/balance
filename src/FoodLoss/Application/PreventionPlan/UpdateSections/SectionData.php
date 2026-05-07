<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\UpdateSections;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSectionId;

final class SectionData
{
    public function __construct(
        public ?PreventionPlanSectionId $sectionId = null,
        public string $title = '',
        public string $content = '',
    ) {}
}
