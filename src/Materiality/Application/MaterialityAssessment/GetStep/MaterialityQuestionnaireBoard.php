<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\GetStep;

final readonly class MaterialityQuestionnaireBoard
{
    /**
     * @param list<MaterialityQuestionnaireGroup>  $groups
     * @param list<MaterialityQuestionnaireColumn> $columns
     */
    public function __construct(
        public array $groups,
        public array $columns,
        public int $answered,
        public int $total,
        public bool $fullyAnswered,
        public ?int $inheritedFromYear,
    ) {}
}
