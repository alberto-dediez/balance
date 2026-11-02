<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Start;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Application\CommandBus\Command;

class StartMaterialityAssessmentRequest implements Command
{
    public function __construct(
        public MaterialityAssessmentSubjectType $subjectType,
        public string $carbonFootprintId,
        public ?MaterialitySectorId $sectorId = null,
        public bool $copyPreviousAnswers = false,
    ) {}
}
