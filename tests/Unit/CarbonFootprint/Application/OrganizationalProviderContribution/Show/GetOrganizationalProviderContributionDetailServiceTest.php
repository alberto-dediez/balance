<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\Show;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\ReportedEmissionsCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Show\GetOrganizationalProviderContributionDetailRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Show\GetOrganizationalProviderContributionDetailService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;

class GetOrganizationalProviderContributionDetailServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    private GetOrganizationalProviderContributionDetailService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new GetOrganizationalProviderContributionDetailService(
            $this->contributionRepository,
            $this->authorizer,
            $this->locator,
            new StageSectionsBuilder(
                new ActivityCatalogProviderRegistry([
                    new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
                    new ReportedEmissionsCatalogProvider(),
                ]),
                $this->activityRepository,
            ),
            $this->providerRepository,
            $this->propertyRepository,
        );
    }

    public function testReturnsSectionsOfCoveredSourcesWithProviderActivities(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id(), providerId: (string) $provider->id());
        $this->contributionRepository->add($contribution);
        $this->addProviderActivity($footprint, $provider->id(), Source::UpstreamTransport, 100.0);
        $this->addOwnerActivity($footprint, Source::UpstreamTransport, 50.0);

        $response = ($this->sut)(new GetOrganizationalProviderContributionDetailRequest($contribution->id()), $this->owner());

        $this->assertSame($contribution, $response->contribution);
        $this->assertSame($provider, $response->provider);
        $this->assertSame([Source::UpstreamTransport->value], array_keys($response->sectionsBySource));

        $sections = $response->sectionsBySource[Source::UpstreamTransport->value];
        $this->assertSame([ActivityType::Transport, ActivityType::ReportedEmissions], array_map(static fn($section) => $section->definition->activityType, $sections));
        $this->assertCount(0, $sections[0]->activities);
        $this->assertCount(1, $sections[1]->activities);
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetOrganizationalProviderContributionDetailRequest($contribution->id()), $this->plainUser());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetOrganizationalProviderContributionDetailRequest($contribution->id()), $this->owner('other-account'));
    }
}
