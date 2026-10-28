<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\List;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\ProviderContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;

class ListOrganizationalProviderContributionsServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    private ListOrganizationalProviderContributionsService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new ListOrganizationalProviderContributionsService(
            $this->locator,
            $this->contributionRepository,
            $this->providerRepository,
            $this->propertyRepository,
            new ProviderContributionEmissionsCalculator($this->activityRepository),
        );
    }

    public function testListsEmissionsBySourceAndTotal(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider();
        $contribution = OrganizationalProviderContributionMother::requested(
            carbonFootprintId: (string) $footprint->id(),
            providerId: (string) $provider->id(),
            sources: [Source::UpstreamTransport, Source::OperationsWaste],
        );
        $this->contributionRepository->add($contribution);
        $this->addProviderActivity($footprint, $provider->id(), Source::UpstreamTransport, 3200.0);
        $this->addProviderActivity($footprint, $provider->id(), Source::UpstreamTransport, 800.0);
        $this->addProviderActivity($footprint, $provider->id(), Source::OperationsWaste, 100.0);
        $this->addOwnerActivity($footprint, Source::UpstreamTransport, 5000.0);

        $response = ($this->sut)(new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->owner());

        $this->assertCount(1, $response->contributions);
        $summary = $response->contributions[0];
        $this->assertSame($provider, $summary->provider);
        $this->assertNull($summary->property);
        $this->assertEqualsWithDelta(4000.0, (float) $summary->emissionsFor(Source::UpstreamTransport->value)->co2eq(), 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $summary->emissionsFor(Source::OperationsWaste->value)->co2eq(), 0.001);
        $this->assertEqualsWithDelta(4100.0, (float) $summary->total->co2eq(), 0.001);
        $this->assertSame(1, $response->pendingCount());
    }

    public function testAnIgnoredContributionShowsNoEmissions(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider();
        $contribution = OrganizationalProviderContributionMother::manual(
            carbonFootprintId: (string) $footprint->id(),
            providerId: (string) $provider->id(),
        );
        $contribution->ignore();
        $this->contributionRepository->add($contribution);
        $this->addProviderActivity($footprint, $provider->id(), Source::UpstreamTransport, 3200.0);

        $response = ($this->sut)(new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->owner());

        $summary = $response->contributions[0];
        $this->assertEqualsWithDelta(0.0, (float) $summary->emissionsFor(Source::UpstreamTransport->value)->co2eq(), 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $summary->total->co2eq(), 0.001);
    }

    public function testCompanyContributionOnlyCountsItsProperty(): void
    {
        $footprint = $this->companyFootprint();
        $provider = $this->provider();
        $propertyId = PropertyId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(
            carbonFootprintId: (string) $footprint->id(),
            providerId: (string) $provider->id(),
            subjectType: CarbonFootprintReportSubjectType::Company,
            propertyId: (string) $propertyId,
        );
        $this->contributionRepository->add($contribution);
        $this->addProviderActivity($footprint, $provider->id(), Source::UpstreamTransport, 300.0, $propertyId);
        $this->addProviderActivity($footprint, $provider->id(), Source::UpstreamTransport, 700.0);

        $response = ($this->sut)(new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Company, (string) $footprint->id()), $this->owner());

        $this->assertEqualsWithDelta(300.0, (float) $response->contributions[0]->total->co2eq(), 0.001);
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->plainUser());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->owner('other-account'));
    }
}
