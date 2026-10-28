<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionFilter;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;

final class CountedActivityReferences
{
    public function __construct(
        private OrganizationalProviderContributionRepository $contributionRepository,
    ) {}

    /** @return list<ActivityReference> */
    public function of(CarbonFootprint $carbonFootprint): array
    {
        $references = $carbonFootprint->activityReferences();
        $ignoredProviderIds = $this->ignoredProviderIds($carbonFootprint);

        if ([] === $ignoredProviderIds) {
            return $references;
        }

        return array_values(array_filter(
            $references,
            static fn(ActivityReference $reference): bool => null === $reference->placement->providerId
                || !isset($ignoredProviderIds[(string) $reference->placement->providerId]),
        ));
    }

    /** @return array<string, true> */
    private function ignoredProviderIds(CarbonFootprint $carbonFootprint): array
    {
        $subjectType = match (true) {
            $carbonFootprint instanceof PropertyCarbonFootprint => CarbonFootprintReportSubjectType::Property,
            $carbonFootprint instanceof CompanyCarbonFootprint => CarbonFootprintReportSubjectType::Company,
            default => null,
        };

        if (null === $subjectType) {
            return [];
        }

        $ignoredProviderIds = [];
        $contributions = $this->contributionRepository->findByFilter(new OrganizationalProviderContributionFilter(
            subjectType: $subjectType,
            carbonFootprintId: (string) $carbonFootprint->id(),
            status: ProviderContributionStatus::Ignored,
            limit: 0,
        ));

        foreach ($contributions as $contribution) {
            $providerId = $contribution->providerId();

            if (null !== $providerId) {
                $ignoredProviderIds[(string) $providerId] = true;
            }
        }

        return $ignoredProviderIds;
    }
}
