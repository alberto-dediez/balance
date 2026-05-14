<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\FileUploader;

use App\FoodLoss\Application\Incidence\IncidenceEvidenceFileRemover;

class LocalFileIncidenceEvidenceRemover implements IncidenceEvidenceFileRemover
{
    public function __construct(private readonly string $uploadsDir) {}

    public function remove(string $path): void
    {
        $absolutePath = $this->uploadsDir . '/' . $path;

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }
}
