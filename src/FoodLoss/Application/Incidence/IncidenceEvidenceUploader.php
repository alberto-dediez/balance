<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence;

use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\Security\Domain\Model\Account\AccountId;

interface IncidenceEvidenceUploader
{
    public function upload(string $sourcePath, string $extension, AccountId $accountId, IncidenceId $incidenceId): string;
}
