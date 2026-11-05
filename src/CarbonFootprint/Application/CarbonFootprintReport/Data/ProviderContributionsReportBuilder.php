<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\ProviderContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportProviderLine;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;

final class ProviderContributionsReportBuilder
{
    private const array STATUS_ORDER = [
        ProviderContributionStatus::Answered->value => 0,
        ProviderContributionStatus::Pending->value => 1,
        ProviderContributionStatus::Ignored->value => 2,
    ];

    public function __construct(
        private OrganizationalProviderContributionRepository $contributions,
        private ProviderContributionEmissionsCalculator $emissionsCalculator,
        private ProviderNameResolver $providerNameResolver,
    ) {}

    /**
     * @param array<string, string> $propertyNamesById
     *
     * @return list<OrganizationalCarbonFootprintReportProviderLine>
     */
    public function build(CarbonFootprint $footprint, CarbonFootprintReportSubjectType $type, array $propertyNamesById): array
    {
        $lines = array_map(
            fn(OrganizationalProviderContribution $contribution): OrganizationalCarbonFootprintReportProviderLine => $this->lineOf($footprint, $contribution, $propertyNamesById),
            $this->contributions->findActiveByFootprint($type, (string) $footprint->id()),
        );

        usort($lines, static fn(OrganizationalCarbonFootprintReportProviderLine $a, OrganizationalCarbonFootprintReportProviderLine $b): int => [
            self::STATUS_ORDER[$a->status->value],
            $a->providerName,
        ] <=> [
            self::STATUS_ORDER[$b->status->value],
            $b->providerName,
        ]);

        return $lines;
    }

    /** @param array<string, string> $propertyNamesById */
    private function lineOf(CarbonFootprint $footprint, OrganizationalProviderContribution $contribution, array $propertyNamesById): OrganizationalCarbonFootprintReportProviderLine
    {
        $providerId = $contribution->providerId();
        $propertyId = $contribution->propertyId();
        $emissions = $contribution->status()->isIgnored()
            ? '0'
            : $this->emissionsCalculator->total($this->emissionsCalculator->emissionsBySource($footprint, $contribution))->co2eq();

        return new OrganizationalCarbonFootprintReportProviderLine(
            providerName: null === $providerId ? ($contribution->companyName() ?? '-') : $this->providerNameResolver->resolve($providerId),
            propertyName: null === $propertyId ? null : ($propertyNamesById[(string) $propertyId] ?? null),
            sources: $contribution->sources(),
            mode: $contribution->mode(),
            status: $contribution->status(),
            answeredAt: $contribution->answeredAt(),
            emissionsCo2eq: $emissions,
        );
    }
}
