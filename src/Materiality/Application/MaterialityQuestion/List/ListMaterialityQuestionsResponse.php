<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\List;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;

class ListMaterialityQuestionsResponse
{
    /**
     * @param array<MaterialityQuestion>       $materialityQuestions
     * @param array<string, MaterialityTopic>  $topicsById
     * @param array<string, MaterialitySector> $sectorsById
     */
    public function __construct(
        public array $materialityQuestions = [],
        public int $totalResults = 0,
        public array $topicsById = [],
        public array $sectorsById = [],
    ) {}
}
