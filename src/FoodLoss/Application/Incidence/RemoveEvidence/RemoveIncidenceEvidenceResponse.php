<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\RemoveEvidence;

use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveIncidenceEvidenceResponse implements CommandResponse
{
    public function __construct(
        public IncidenceEvidenceId $evidenceId,
    ) {}
}
