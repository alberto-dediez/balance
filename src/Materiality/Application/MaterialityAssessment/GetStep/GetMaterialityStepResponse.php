<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryRow;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;

class GetMaterialityStepResponse
{
    /**
     * @param list<MaterialitySector>     $sectors
     * @param list<MaterialitySummaryRow> $summaryRows
     */
    public function __construct(
        public ?MaterialityAssessment $assessment,
        public array $sectors,
        public ?int $previousYear,
        public ?MaterialitySectorId $previousSectorId,
        public bool $footprintCompleted,
        public bool $materialityAnswered,
        public array $summaryRows = [],
        public bool $stageExcluded = false,
        public bool $pendingReview = false,
        public ?MaterialityQuestionnaireBoard $board = null,
    ) {}
}
