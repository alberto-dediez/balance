<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;

final readonly class MaterialityQuestionnaireColumn
{
    public function __construct(
        public MaterialityQuestionId $questionId,
        public string $text,
        public string $help,
        public MaterialityCriterion $criterion,
        public int $weight,
        public MaterialityAnswerScale $answerScale,
    ) {}
}
