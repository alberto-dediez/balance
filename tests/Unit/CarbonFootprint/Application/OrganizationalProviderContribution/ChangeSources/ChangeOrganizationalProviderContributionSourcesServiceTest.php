<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\ChangeSources;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\ChangeSources\ChangeOrganizationalProviderContributionSourcesRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\ChangeSources\ChangeOrganizationalProviderContributionSourcesService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;

class ChangeOrganizationalProviderContributionSourcesServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    public function testRemovesProviderActivitiesOfUncoveredSources(): void
    {
        $footprint = $this->propertyFootprint();
        $providerId = ProviderId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(
            carbonFootprintId: (string) $footprint->id(),
            providerId: (string) $providerId,
            sources: [Source::UpstreamTransport, Source::OperationsWaste],
        );
        $this->contributionRepository->add($contribution);
        $this->addProviderActivity($footprint, $providerId, Source::UpstreamTransport, 100.0);
        $this->addProviderActivity($footprint, $providerId, Source::OperationsWaste, 50.0);
        $this->addOwnerActivity($footprint, Source::OperationsWaste, 25.0);

        $sut = new ChangeOrganizationalProviderContributionSourcesService($this->contributionRepository, $this->authorizer, $this->locator);
        $sut(new ChangeOrganizationalProviderContributionSourcesRequest($contribution->id(), [Source::UpstreamTransport, Source::BusinessTravel]), $this->owner());

        $this->assertSame([Source::UpstreamTransport, Source::BusinessTravel], $contribution->sources());
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $providerId)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::OperationsWaste, $providerId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::OperationsWaste)));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->completedPropertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        (new ChangeOrganizationalProviderContributionSourcesService($this->contributionRepository, $this->authorizer, $this->locator))(
            new ChangeOrganizationalProviderContributionSourcesRequest($contribution->id(), [Source::OperationsWaste]),
            $this->owner(),
        );
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        (new ChangeOrganizationalProviderContributionSourcesService($this->contributionRepository, $this->authorizer, $this->locator))(
            new ChangeOrganizationalProviderContributionSourcesRequest($contribution->id(), [Source::OperationsWaste]),
            $this->plainUser(),
        );
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        (new ChangeOrganizationalProviderContributionSourcesService($this->contributionRepository, $this->authorizer, $this->locator))(
            new ChangeOrganizationalProviderContributionSourcesRequest($contribution->id(), [Source::OperationsWaste]),
            $this->owner('other-account'),
        );
    }
}
