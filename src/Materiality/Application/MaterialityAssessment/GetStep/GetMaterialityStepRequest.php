<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;

class GetMaterialityStepRequest
{
    public function __construct(
        public MaterialityAssessmentSubjectType $subjectType,
        public string $carbonFootprintId,
    ) {}
}
