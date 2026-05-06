<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Domain\Model\Locale\Locale;

interface PreventionPlanPdfUploader
{
    public function upload(string $sourcePath, PreventionPlanId $preventionPlanId, int $versionNumber, Locale $locale): string;
}
