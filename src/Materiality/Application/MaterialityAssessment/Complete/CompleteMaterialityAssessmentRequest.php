<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Complete;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Shared\Application\CommandBus\Command;

class CompleteMaterialityAssessmentRequest implements Command
{
    public function __construct(
        public MaterialityAssessmentId $materialityAssessmentId,
    ) {}
}
