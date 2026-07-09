<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AnswerPhase;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;

final class AnswerProviderContributionPhaseRequest
{
    /** @param list<SectionAnswer> $sectionAnswers */
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly Phase $phase,
        public readonly array $sectionAnswers,
    ) {}
}
