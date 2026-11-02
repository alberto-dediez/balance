<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;

class GetMaterialityStepResponse
{
    /** @param list<MaterialitySector> $sectors */
    public function __construct(
        public ?MaterialityAssessment $assessment,
        public array $sectors,
        public ?int $previousYear,
        public ?MaterialitySectorId $previousSectorId,
        public bool $footprintCompleted,
        public bool $materialityAnswered,
    ) {}
}
