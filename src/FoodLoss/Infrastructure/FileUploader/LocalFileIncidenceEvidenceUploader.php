<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\FileUploader;

use App\FoodLoss\Application\Incidence\IncidenceEvidenceUploader;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\Security\Domain\Model\Account\AccountId;

class LocalFileIncidenceEvidenceUploader implements IncidenceEvidenceUploader
{
    public function __construct(private readonly string $uploadsDir) {}

    public function upload(string $sourcePath, string $extension, AccountId $accountId, IncidenceId $incidenceId): string
    {
        $fileName = $incidenceId . '-' . uniqid() . '.' . $extension;
        $relativePath = 'incidence_evidences/' . $accountId . '/' . $fileName;
        $absoluteDestination = $this->uploadsDir . '/' . $relativePath;

        if (!is_dir(dirname($absoluteDestination))) {
            mkdir(dirname($absoluteDestination), 0755, true);
        }

        rename($sourcePath, $absoluteDestination);

        return $relativePath;
    }
}
