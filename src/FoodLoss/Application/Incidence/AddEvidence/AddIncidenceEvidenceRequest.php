<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\AddEvidence;

use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class AddIncidenceEvidenceRequest implements Command
{
    public function __construct(
        public IncidenceId $incidenceId,
        public AccountId $accountId,
        public string $temporaryFilePath,
        public string $fileExtension,
        public string $originalName,
        public string $mimeType,
    ) {}
}
