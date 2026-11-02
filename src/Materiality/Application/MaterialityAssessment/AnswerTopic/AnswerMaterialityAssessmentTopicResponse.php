<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment\AnswerTopic;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;
use App\Shared\Application\CommandBus\CommandResponse;

class AnswerMaterialityAssessmentTopicResponse implements CommandResponse
{
    public function __construct(
        public ?MaterialityAssessmentTopicId $nextTopicId,
    ) {}
}
