<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Show;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\Property\Domain\Model\Property\Property;

final class GetOrganizationalProviderContributionDetailResponse
{
    /** @param array<string, list<StageSection>> $sectionsBySource */
    public function __construct(
        public readonly OrganizationalProviderContribution $contribution,
        public readonly PropertyCarbonFootprint|CompanyCarbonFootprint $carbonFootprint,
        public readonly ?Provider $provider,
        public readonly ?Property $property,
        public readonly array $sectionsBySource,
    ) {}
}
