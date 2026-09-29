<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Update;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Application\CommandBus\Command;

class UpdateMaterialityQuestionRequest implements Command
{
    /** @param list<MaterialitySectorId> $sectorIds */
    public function __construct(
        public MaterialityQuestionId $materialityQuestionId,
        public MaterialityCriterion $criterion,
        public ?MaterialityTopicId $topicId,
        public string $text,
        public string $help,
        public MaterialityAnswerScale $answerScale,
        public int $weight,
        public int $position,
        public bool $active,
        public array $sectorIds = [],
    ) {}
}
