<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetTopic;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;

class GetMaterialityAssessmentTopicRequest
{
    public function __construct(
        public MaterialityAssessmentId $materialityAssessmentId,
        public MaterialityAssessmentTopicId $assessmentTopicId,
    ) {}
}
