<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\EventOperations\Domain\Model\Provider\ProviderId;

final class ProviderContributionPhaseSectionsBuilder
{
    public function __construct(
        private StageSectionsBuilder $sectionsBuilder,
    ) {}

    /** @return list<StageSection> */
    public function build(EventCarbonFootprint $eventCarbonFootprint, Phase $phase, ProviderId $providerId): array
    {
        return $this->sectionsBuilder->build($eventCarbonFootprint, $phase, $phase->providerSections(), $providerId)->sections;
    }
}
