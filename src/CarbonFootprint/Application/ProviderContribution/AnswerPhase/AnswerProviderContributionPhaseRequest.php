<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AnswerPhase;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;

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
