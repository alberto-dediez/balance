<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanPdfUploader;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Domain\Model\Locale\Locale;

final class NullPreventionPlanPdfUploader implements PreventionPlanPdfUploader
{
    public function upload(string $sourcePath, PreventionPlanId $preventionPlanId, int $versionNumber, Locale $locale): string
    {
        return 'prevention_plans/test.pdf';
    }
}
