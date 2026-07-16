<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\Shared\Application\CommandBus\Command;

class AnswerPhaseRequest implements Command
{
    /**
     * @param list<SectionAnswer>   $sectionAnswers
     * @param list<ExclusionAnswer> $exclusions
     */
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public Phase $phase,
        public array $sectionAnswers = [],
        public array $exclusions = [],
    ) {}
}
