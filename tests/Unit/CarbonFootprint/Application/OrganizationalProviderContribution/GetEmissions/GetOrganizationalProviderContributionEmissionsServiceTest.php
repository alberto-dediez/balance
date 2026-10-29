<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\GetEmissions;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\ReportedEmissionsCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetEmissions\GetOrganizationalProviderContributionEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetEmissions\GetOrganizationalProviderContributionEmissionsService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionPublicServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;

class GetOrganizationalProviderContributionEmissionsServiceTest extends OrganizationalProviderContributionPublicServiceTestCase
{
    private GetOrganizationalProviderContributionEmissionsService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new GetOrganizationalProviderContributionEmissionsService(
            $this->authenticator,
            new StageSectionsBuilder(
                new ActivityCatalogProviderRegistry([
                    new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
                    new ReportedEmissionsCatalogProvider(),
                ]),
                $this->activityRepository,
            ),
        );
    }

    public function testReturnsSectionsOfCoveredSourcesWithOnlyTheProviderActivities(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->linkedProvider();
        $contribution = $this->requestedContribution((string) $footprint->id(), $provider);
        $this->addProviderActivity($footprint, $provider->id(), Source::UpstreamTransport, 100.0);
        $this->addOwnerActivity($footprint, Source::UpstreamTransport, 50.0);

        $response = ($this->sut)(new GetOrganizationalProviderContributionEmissionsRequest($contribution->token()));

        $this->assertSame($contribution, $response->contribution);
        $this->assertSame([Source::UpstreamTransport->value], array_keys($response->sectionsBySource));

        $sections = $response->sectionsBySource[Source::UpstreamTransport->value];
        $this->assertSame([ActivityType::Transport, ActivityType::ReportedEmissions], array_map(static fn($section) => $section->definition->activityType, $sections));
        $this->assertCount(0, $sections[0]->activities);
        $this->assertCount(1, $sections[1]->activities);
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(OrganizationalProviderContributionTokenInvalidException::class);

        ($this->sut)(new GetOrganizationalProviderContributionEmissionsRequest('invalid-token'));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $footprint->answerProviders();
        $footprint->answerEmployees();
        $footprint->complete($this->completedEmissions());
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new GetOrganizationalProviderContributionEmissionsRequest($contribution->token()));
    }
}
