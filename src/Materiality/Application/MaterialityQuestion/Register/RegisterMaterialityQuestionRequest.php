<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityQuestion\Register;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityCriterion;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Application\CommandBus\Command;

class RegisterMaterialityQuestionRequest implements Command
{
    /** @param list<MaterialitySectorId> $sectorIds */
    public function __construct(
        public MaterialityCriterion $criterion = MaterialityCriterion::Magnitude,
        public ?MaterialityTopicId $topicId = null,
        public string $text = '',
        public string $help = '',
        public MaterialityAnswerScale $answerScale = MaterialityAnswerScale::Level,
        public int $weight = 1,
        public int $position = 0,
        public array $sectorIds = [],
    ) {}
}
