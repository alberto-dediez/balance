<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetTopic;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAnswer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopic;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;

class GetMaterialityAssessmentTopicResponse
{
    /** @param list<MaterialityAssessmentAnswer> $answers */
    public function __construct(
        public MaterialityAssessment $assessment,
        public MaterialityAssessmentTopic $topic,
        public array $answers,
        public int $index,
        public int $total,
        public ?MaterialityAssessmentTopicId $previousTopicId,
        public ?MaterialityAssessmentTopicId $nextTopicId,
        public CarbonFootprint $footprint,
        public bool $readOnly,
    ) {}
}
