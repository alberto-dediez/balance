<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Complete;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Shared\Application\CommandBus\CommandResponse;

class CompleteMaterialityAssessmentResponse implements CommandResponse
{
    public function __construct(
        public MaterialityAssessmentId $materialityAssessmentId,
    ) {}
}
