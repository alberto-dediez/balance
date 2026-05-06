<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\FileUploader;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanPdfUploader;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Domain\Model\Locale\Locale;

class LocalFilePreventionPlanPdfUploader implements PreventionPlanPdfUploader
{
    public function __construct(private readonly string $uploadsDir) {}

    public function upload(string $sourcePath, PreventionPlanId $preventionPlanId, int $versionNumber, Locale $locale): string
    {
        $fileName = $preventionPlanId . '-v' . $versionNumber . '-' . $locale->value . '.pdf';
        $relativePath = 'prevention_plans/' . $fileName;
        $absoluteDestination = $this->uploadsDir . '/' . $relativePath;

        if (!is_dir(dirname($absoluteDestination))) {
            mkdir(dirname($absoluteDestination), 0755, true);
        }

        rename($sourcePath, $absoluteDestination);

        return $relativePath;
    }
}
