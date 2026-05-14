<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\RemoveEvidence;

use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\Shared\Application\CommandBus\Command;

class RemoveIncidenceEvidenceRequest implements Command
{
    public function __construct(
        public IncidenceId $incidenceId,
        public IncidenceEvidenceId $evidenceId,
    ) {}
}
