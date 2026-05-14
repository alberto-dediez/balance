<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence;

interface IncidenceEvidenceFileRemover
{
    public function remove(string $path): void;
}
