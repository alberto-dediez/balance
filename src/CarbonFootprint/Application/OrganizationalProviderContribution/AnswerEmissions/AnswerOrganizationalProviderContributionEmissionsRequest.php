<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerEmissions;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;

final class AnswerOrganizationalProviderContributionEmissionsRequest
{
    /** @param list<SectionAnswer> $sectionAnswers */
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly array $sectionAnswers,
    ) {}
}
