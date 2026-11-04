<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;

final readonly class MaterialityQuestionnaireGroup
{
    /** @param list<MaterialityQuestionnaireRow> $rows */
    public function __construct(
        public MaterialityScope $scope,
        public ?MaterialityTopicDirection $direction,
        public array $rows,
        public int $answered,
        public int $total,
    ) {}
}
