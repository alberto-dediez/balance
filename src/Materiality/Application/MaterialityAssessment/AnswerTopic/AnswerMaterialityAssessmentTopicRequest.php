<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\AnswerTopic;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;
use App\Shared\Application\CommandBus\Command;

class AnswerMaterialityAssessmentTopicRequest implements Command
{
    /** @param array<string, int|null> $valuesByAnswerId */
    public function __construct(
        public MaterialityAssessmentId $materialityAssessmentId,
        public MaterialityAssessmentTopicId $assessmentTopicId,
        public array $valuesByAnswerId,
    ) {}
}
