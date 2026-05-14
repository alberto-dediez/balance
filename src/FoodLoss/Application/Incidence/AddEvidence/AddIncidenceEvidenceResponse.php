<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\AddEvidence;

use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\Shared\Application\CommandBus\CommandResponse;

class AddIncidenceEvidenceResponse implements CommandResponse
{
    public function __construct(
        public IncidenceEvidenceId $evidenceId,
        public string $path,
    ) {}
}
