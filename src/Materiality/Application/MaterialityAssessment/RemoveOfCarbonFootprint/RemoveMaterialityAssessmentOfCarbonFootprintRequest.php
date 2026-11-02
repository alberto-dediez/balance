<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Shared\Application\CommandBus\Command;

class RemoveMaterialityAssessmentOfCarbonFootprintRequest implements Command
{
    public function __construct(
        public MaterialityAssessmentSubjectType $subjectType,
        public string $carbonFootprintId,
    ) {}
}
