<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\RecordManualEmissions;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\Shared\Application\CommandBus\Command;

class RecordOrganizationalProviderContributionManualEmissionsRequest implements Command
{
    /** @param list<SectionAnswer> $sectionAnswers */
    public function __construct(
        public OrganizationalProviderContributionId $contributionId,
        public array $sectionAnswers,
    ) {}
}
