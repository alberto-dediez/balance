<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;

final readonly class OrganizationalCarbonFootprintReportProviderLine
{
    /** @param list<Source> $sources */
    public function __construct(
        public string $providerName,
        public ?string $propertyName,
        public array $sources,
        public ProviderContributionMode $mode,
        public ProviderContributionStatus $status,
        public ?\DateTimeImmutable $answeredAt,
        public string $emissionsCo2eq,
    ) {}
}
