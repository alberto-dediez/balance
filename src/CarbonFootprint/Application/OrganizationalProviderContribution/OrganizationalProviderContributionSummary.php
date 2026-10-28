<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\Property\Domain\Model\Property\Property;

final class OrganizationalProviderContributionSummary
{
    /** @param array<string, Emissions> $emissionsBySource */
    public function __construct(
        public readonly OrganizationalProviderContribution $contribution,
        public readonly ?Provider $provider,
        public readonly ?Property $property,
        public readonly array $emissionsBySource,
        public readonly Emissions $total,
    ) {}

    public function emissionsFor(string $source): Emissions
    {
        return $this->emissionsBySource[$source] ?? new Emissions();
    }
}
