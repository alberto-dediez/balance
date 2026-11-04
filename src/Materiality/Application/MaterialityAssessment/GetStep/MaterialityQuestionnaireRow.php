<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentAnswer;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopic;

final readonly class MaterialityQuestionnaireRow
{
    /**
     * @param array<string, MaterialityAssessmentAnswer> $genericAnswers
     * @param list<MaterialityAssessmentAnswer>          $specificAnswers
     */
    public function __construct(
        public MaterialityAssessmentTopic $topic,
        public array $genericAnswers,
        public array $specificAnswers,
        public int $answered,
        public int $total,
    ) {}
}
