<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\Start;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;
use App\Shared\Application\CommandBus\CommandResponse;

class StartMaterialityAssessmentResponse implements CommandResponse
{
    public function __construct(
        public MaterialityAssessmentId $materialityAssessmentId,
        public ?MaterialityAssessmentTopicId $firstTopicId,
    ) {}
}
