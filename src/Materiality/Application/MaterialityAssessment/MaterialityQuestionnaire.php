<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityQuestionSnapshot;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityTopicSnapshot;

final readonly class MaterialityQuestionnaire
{
    /**
     * @param list<MaterialityTopicSnapshot>    $topics
     * @param list<MaterialityQuestionSnapshot> $questions
     */
    public function __construct(
        public array $topics,
        public array $questions,
    ) {}
}
